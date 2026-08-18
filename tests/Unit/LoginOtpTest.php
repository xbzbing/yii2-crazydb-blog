<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Captcha\CaptchaService;
use App\Tests\TestCase;
use App\User\User;
use App\User\UserTotpService;
use App\Web\Login\Action as LoginAction;
use HttpSoft\Message\ServerRequestFactory;
use OTPHP\TOTP;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\SessionInterface;

/**
 * 登录 OTP 分支集成测试（真库 + 容器）：
 * - 启用 OTP 用户：密码正确但 OTP 缺失/错误 → 登录失败（统一文案）
 * - OTP 正确 → 登录成功（302 + 记住我 cookie）
 * - 防重放：同一码在 TTL 内重复提交 → 拒绝
 *
 * captcha 用 debug 模式绕过（与 dev 环境 CAPTCHA_DEBUG=1 一致）。
 */
final class LoginOtpTest extends TestCase
{
    private UserTotpService $totpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totpService = new UserTotpService();
    }

    /**
     * 创建启用 OTP 的测试用户。
     *
     * @return array{user: User, totp: TOTP, cleanup: callable}
     */
    private function createOtpUser(string $suffix): array
    {
        $user = new User();
        $user->username = 'otp_' . $suffix;
        $user->nickname = 'OTP测试' . $suffix;
        $user->email = $suffix . '@example.com';
        $user->password = 'password123';
        $user->role = User::ROLE_ADMIN;
        $user->otp_secret = $this->totpService->generateSecret();
        $user->otp_enabled = 1;
        $user->fillDefaultsForInsert('203.0.113.20');
        $user->save();

        $totp = TOTP::createFromSecret($user->otp_secret);
        return ['user' => $user, 'totp' => $totp, 'cleanup' => static fn() => $user->delete()];
    }

    private function loginAction(): LoginAction
    {
        $container = $this->container();
        $session = $this->sharedSession();
        $session->open();

        // console 测试容器无 web 层定义：仿 MaintenanceMiddlewareTest 手工组装
        $renderer = new \Yiisoft\Yii\View\Renderer\WebViewRenderer(
            new \HttpSoft\Message\ResponseFactory(),
            new \HttpSoft\Message\StreamFactory(),
            $container->get(\Yiisoft\Aliases\Aliases::class),
            $container->get(\Yiisoft\View\WebView::class),
            viewPath: dirname(__DIR__, 2) . '/src/Web',
            layout: null,
        );

        return new LoginAction(
            $renderer,
            new \HttpSoft\Message\ResponseFactory(),
            $container->get(UrlGeneratorInterface::class),
            $container->get(\Yiisoft\Cache\CacheInterface::class),
            $container->get(\App\User\LoginThrottle::class),
            $container->get(\App\User\AuthService::class),
            new CaptchaService($session, true), // debug：验证码直通
            $this->totpService,
            $container->get(\Yiisoft\Session\Flash\FlashInterface::class),
        );
    }

    /**
     * @param array<string, string> $body
     */
    private function postLogin(LoginAction $action, array $body): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/login');
        $request = $request->withParsedBody($body + ['captcha' => 'debug', 'rememberMe' => '1']);
        return $action($request);
    }

    private function validCode(TOTP $totp): string
    {
        return $totp->now();
    }

    public function testOtpUserLoginFailsWithoutOtpCode(): void
    {
        ['user' => $user, 'cleanup' => $cleanup] = $this->createOtpUser('missing');
        try {
            $response = $this->postLogin($this->loginAction(), [
                'username' => $user->username,
                'password' => 'password123',
                'otp_code' => '',
            ]);
            // 登录失败：渲染登录页（200），不重定向
            self::assertSame(200, $response->getStatusCode());
        } finally {
            $cleanup();
        }
    }

    public function testOtpUserLoginFailsWithWrongOtpCode(): void
    {
        ['user' => $user, 'cleanup' => $cleanup] = $this->createOtpUser('wrong');
        try {
            $response = $this->postLogin($this->loginAction(), [
                'username' => $user->username,
                'password' => 'password123',
                'otp_code' => '000000',
            ]);
            self::assertSame(200, $response->getStatusCode());
        } finally {
            $cleanup();
        }
    }

    public function testOtpUserLoginSucceedsWithValidOtpCode(): void
    {
        ['user' => $user, 'totp' => $totp, 'cleanup' => $cleanup] = $this->createOtpUser('ok');
        try {
            $response = $this->postLogin($this->loginAction(), [
                'username' => $user->username,
                'password' => 'password123',
                'otp_code' => $this->validCode($totp),
            ]);
            self::assertSame(302, $response->getStatusCode(), 'OTP 正确应重定向（登录成功）');
            self::assertNotEmpty($response->getHeader('Set-Cookie'), '记住我应写入 cookie');
        } finally {
            $cleanup();
        }
    }

    public function testOtpUserLoginRejectsReplayedCode(): void
    {
        ['user' => $user, 'totp' => $totp, 'cleanup' => $cleanup] = $this->createOtpUser('replay');
        $session = $this->sharedSession();
        try {
            $code = $this->validCode($totp);

            // 第一次登录成功（写入防重放缓存）
            $first = $this->postLogin($this->loginAction(), [
                'username' => $user->username,
                'password' => 'password123',
                'otp_code' => $code,
            ]);
            self::assertSame(302, $first->getStatusCode());

            // 模拟登出：清掉 session 登录态（AuthService 已登录会直接重定向，干扰重放断言）
            $session->remove(User::SESSION_AUTH_KEY);

            // 同一码再次提交：防重放拒绝，渲染登录页
            $second = $this->postLogin($this->loginAction(), [
                'username' => $user->username,
                'password' => 'password123',
                'otp_code' => $code,
            ]);
            self::assertSame(200, $second->getStatusCode(), '同一 OTP 码在 TTL 内重放应被拒绝');
        } finally {
            $session->remove(User::SESSION_AUTH_KEY);
            $cleanup();
        }
    }
}
