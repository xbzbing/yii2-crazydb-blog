<?php

declare(strict_types=1);

namespace App\Web\Login;

use App\Captcha\CaptchaService;
use App\Common\CMSUtils;
use App\Log\Log;
use App\Nav\Nav;
use App\User\AuthService;
use App\User\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 登录（等价 Yii2 SiteController::actionLogin）：
 * 暴力破解防护（session 失败计数，5 次锁 15 分钟）→ 表单校验（验证码/密码）→
 * AuthService::login → Log 记录 → 回首页。
 */
final readonly class Action
{
    private const MAX_FAILURES = 5;
    private const LOCK_SECONDS = 900;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private SessionInterface $session,
        private AuthService $authService,
        private CaptchaService $captcha,
        private FlashInterface $flash,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->authService->isLoggedIn()) {
            return $this->redirectHome();
        }

        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        $username = '';
        $locked = $this->lockRemaining() > 0;

        if (!$locked && $request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            $data = is_array($body) ? $body : [];
            $username = trim((string)($data['username'] ?? ''));
            $password = (string)($data['password'] ?? '');
            $rememberMe = filter_var($data['rememberMe'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$this->captcha->validate((string)($data['captcha'] ?? ''))) {
                $this->recordFailure($request, $username, '验证码错误');
            } else {
                $user = $username !== '' ? User::findByUsername($username) : null;
                if ($user === null || !$user->validatePassword($password)) {
                    $this->recordFailure($request, $username, '用户名和密码不匹配。');
                } else {
                    try {
                        $this->authService->login($user, $rememberMe);
                        $this->session->remove('login_failures');
                        $this->session->remove('login_locked_until');
                        (new Log())->record(Log::TYPE_LOGIN, 'site/login', (string)$user->id, Log::STATUS_SUCCESS, "用户「{$username}」成功!", (int)$user->id);
                        return $this->redirectHome();
                    } catch (\App\Common\CMSException $e) {
                        $this->recordFailure($request, $username, $e->getMessage());
                    }
                }
            }
        }

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'username' => $username,
                'locked' => $locked,
                'lockRemaining' => $this->lockRemaining(),
                'siteConfig' => $siteConfig,
                'navTree' => Nav::getNavTree($this->cache),
                'showSidebar' => false,
            ],
        );
    }

    /**
     * 记录一次登录失败：计数 +1，达到上限锁定；锁定期间不写日志（防刷爆 log 表）。
     */
    private function recordFailure(ServerRequestInterface $request, string $username, string $reason): void
    {
        $failures = (int)$this->session->get('login_failures') + 1;
        if ($failures >= self::MAX_FAILURES) {
            $this->session->set('login_failures', 0);
            $this->session->set('login_locked_until', time() + self::LOCK_SECONDS);
        } else {
            $this->session->set('login_failures', $failures);
        }
        (new Log())->record(Log::TYPE_LOGIN, 'site/login', (string)$request->getUri()->getPath(), Log::STATUS_FAILED, "用户「{$username}」登录失败！原因：{$reason}");
        $this->flash->set('flash_error', ['info' => $reason]);
    }

    /**
     * 剩余锁定秒数（0 = 未锁定）。
     */
    private function lockRemaining(): int
    {
        $lockedUntil = (int)$this->session->get('login_locked_until');
        return $lockedUntil > time() ? $lockedUntil - time() : 0;
    }

    private function redirectHome(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('site/index'));
    }
}
