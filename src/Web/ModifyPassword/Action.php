<?php

declare(strict_types=1);

namespace App\Web\ModifyPassword;

use App\Common\CMSUtils;
use App\Nav\Nav;
use App\User\AuthService;
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
 * 修改密码（等价 Yii2 UserController::actionModifyPassword）：需登录。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private AuthService $authService,
        private FlashInterface $flash,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->authService->currentUser();
        if ($user === null) {
            return $this->responseFactory
                ->createResponse(Status::FOUND)
                ->withHeader('Location', $this->urlGenerator->generate('site/login'));
        }

        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        $errors = [];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            $data = is_array($body) ? $body : [];
            $oldPassword = (string)($data['old_password'] ?? '');
            $newPassword = (string)($data['password'] ?? '');
            $repeat = (string)($data['password_repeat'] ?? '');

            if ($newPassword !== $repeat) {
                $errors['password_repeat'] = '两次输入的新密码不一致。';
            }
            if ($errors === []) {
                $error = $this->authService->changePassword($user, $oldPassword, $newPassword);
                if ($error !== null) {
                    $errors['old_password'] = $error;
                } else {
                    // 改密成功：清除当前会话登录态（与"请重新登录"文案一致），旧记住我 token 已因 auth_key 轮换失效
                    $this->authService->logout();
                    $this->flash->set('flash_success', ['info' => '密码已修改，请使用新密码重新登录。']);
                    return $this->responseFactory
                        ->createResponse(Status::FOUND)
                        ->withHeader('Location', $this->urlGenerator->generate('site/login'));
                }
            }
        }

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'errors' => $errors,
                'siteConfig' => $siteConfig,
                'navTree' => Nav::getNavTree($this->cache, $this->urlGenerator),
                'showSidebar' => false,
            ],
        );
    }
}
