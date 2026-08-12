<?php

declare(strict_types=1);

namespace App\Web\UserProfile;

use App\Common\CMSUtils;
use App\Nav\Nav;
use App\User\AuthService;
use App\User\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 修改个人资料（等价 Yii2 UserController::actionProfile）：需登录。
 * 可改昵称/邮箱/个人网站/个人简介（用户名与角色不可改）。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private Aliases $aliases,
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
            $errors = $this->authService->updateProfile($user, $data);
            if ($errors === []) {
                $this->flash->set('flash_success', ['info' => '个人资料已更新。']);
                return $this->responseFactory
                    ->createResponse(Status::FOUND)
                    ->withHeader('Location', $this->urlGenerator->generate('user/show', ['name' => $user->nickname]));
            }
        }

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'user' => $user,
                'errors' => $errors,
                'siteConfig' => $siteConfig,
                'navTree' => Nav::getNavTree($this->cache, $this->urlGenerator),
                'showSidebar' => false,
                'aliases' => $this->aliases,
            ],
        );
    }
}
