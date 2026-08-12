<?php

declare(strict_types=1);

namespace App\Web\Login;

use App\Captcha\CaptchaService;
use App\Common\CMSUtils;
use App\Log\Log;
use App\Nav\Nav;
use App\User\AuthService;
use App\User\RememberMeMiddleware;
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
 * AuthService::login → Log 记录 → 回首页或 redirect 目标（仅站内路径，防开放重定向）。
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
        private bool $rememberCookieSecure = false,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // redirect 目标：GET 从 query、POST 从 body 读取；仅允许站内路径（防开放重定向）
        $query = $request->getQueryParams();
        $body = $request->getParsedBody();
        $redirectRaw = (string)(is_array($body) && ($body['redirect'] ?? '') !== '' ? $body['redirect'] : ($query['redirect'] ?? ''));
        $redirect = $this->normalizeRedirect($redirectRaw);

        if ($this->authService->isLoggedIn()) {
            return $this->redirectTo($redirect);
        }

        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        $username = '';
        $locked = $this->lockRemaining() > 0;

        if (!$locked && $request->getMethod() === Method::POST) {
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
                        $token = $this->authService->login($user, $rememberMe);
                        $this->session->remove('login_failures');
                        $this->session->remove('login_locked_until');
                        (new Log())->record(Log::TYPE_LOGIN, 'site/login', (string)$user->id, Log::STATUS_SUCCESS, "用户「{$username}」成功!", (int)$user->id);
                        $response = $this->redirectTo($redirect);
                        if ($token !== null) {
                            $response = $response->withHeader(
                                'Set-Cookie',
                                RememberMeMiddleware::buildCookie($token, $this->rememberCookieSecure),
                            );
                        }
                        return $response;
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
                'redirect' => $redirect,
                'siteConfig' => $siteConfig,
                'navTree' => Nav::getNavTree($this->cache, $this->urlGenerator),
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

    private function redirectTo(string $redirect): ResponseInterface
    {
        return $redirect !== ''
            ? $this->responseFactory->createResponse(Status::FOUND)->withHeader('Location', $redirect)
            : $this->redirectHome();
    }

    /**
     * 校验并归一化 redirect 目标：仅允许站内相对路径（以 / 开头且非 // 开头），
     * 拒绝外链与协议相对 URL，防开放重定向。
     */
    private function normalizeRedirect(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '/') {
            return '';
        }
        // 站内路径：以单个 / 开头，且不以 // 或 /\ 开头（防协议相对/反斜杠绕过）
        if (str_starts_with($raw, '/') && !str_starts_with($raw, '//') && !str_starts_with($raw, '/\\')) {
            // 限制长度避免异常超长输入
            return mb_substr($raw, 0, 512);
        }
        return '';
    }
}
