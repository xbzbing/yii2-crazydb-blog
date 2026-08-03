<?php

declare(strict_types=1);

namespace App\Web\Logout;

use App\User\AuthService;
use App\User\RememberMeMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * 登出（等价 Yii2 SiteController::actionLogout）：清 session 登录态 + 记住我 cookie 后回首页。
 */
final readonly class Action
{
    public function __construct(
        private AuthService $authService,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $this->authService->logout();
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('site/index'))
            ->withHeader(
                'Set-Cookie',
                RememberMeMiddleware::COOKIE_NAME . '=; Path=/; Max-Age=0; HttpOnly; SameSite=Lax',
            );
    }
}
