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
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 登录（等价 Yii2 SiteController::actionLogin）：
 * 表单校验（用户名/密码/验证码/记住我）→ AuthService::login → Log 记录 → 回首页。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
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
        $error = '';

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            $data = is_array($body) ? $body : [];
            $username = trim((string)($data['username'] ?? ''));
            $password = (string)($data['password'] ?? '');
            $rememberMe = filter_var($data['rememberMe'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$this->captcha->validate((string)($data['captcha'] ?? ''))) {
                $error = '验证码错误';
            } else {
                $user = $username !== '' ? User::findByUsername($username) : null;
                if ($user === null || !$user->validatePassword($password)) {
                    $error = '用户名和密码不匹配。';
                } else {
                    try {
                        $this->authService->login($user, $rememberMe);
                        (new Log())->record(Log::TYPE_LOGIN, 'site/login', (string)$user->id, Log::STATUS_SUCCESS, "用户「{$username}」成功!");
                        return $this->redirectHome();
                    } catch (\App\Common\CMSException $e) {
                        $error = $e->getMessage();
                    }
                }
            }
            if ($error !== '') {
                (new Log())->record(Log::TYPE_LOGIN, 'site/login', (string)$request->getUri()->getPath(), Log::STATUS_FAILED, "用户「{$username}」登录失败！");
                $this->flash->set('comment_error', ['info' => $error]);
                $error = '';
            }
        }

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'siteConfig' => $siteConfig,
                'navTree' => Nav::getNavTree($this->cache),
                'showSidebar' => false,
            ],
        );
    }

    private function redirectHome(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('site/index'));
    }
}
