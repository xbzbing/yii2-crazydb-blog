<?php

declare(strict_types=1);

namespace App\Admin;

use App\User\AuthService;
use App\User\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * 后台访问守卫：仅管理员（role=ADMIN）可进入 /admin/*，否则重定向登录页。
 */
final readonly class AdminGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthService $authService,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->authService->currentUser();
        if ($user instanceof User && $user->role === User::ROLE_ADMIN && $user->isNormal()) {
            return $handler->handle($request);
        }
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('site/login'));
    }
}
