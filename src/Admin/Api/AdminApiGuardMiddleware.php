<?php

declare(strict_types=1);

namespace App\Admin\Api;

use App\User\AuthService;
use App\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Csrf\CsrfTokenInterface;
use Yiisoft\Http\Status;

/**
 * 后台 JSON API 守卫：仅管理员（role=ADMIN）可访问 /admin/api/*，
 * 失败返回 401/403 JSON（区别于 HTML 后台的 302 重定向登录页）。
 *
 * 未登录时（401）也会返回同步器 CSRF token（csrf 字段），
 * 供 SPA 登录表单提交 /login 使用——token 存 session，与登录后同一把。
 */
final readonly class AdminApiGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthService $authService,
        private JsonResponse $jsonResponse,
        private CsrfTokenInterface $csrfToken,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->authService->currentUser();
        if ($user instanceof User && $user->role === User::ROLE_ADMIN && $user->isNormal()) {
            return $handler->handle($request);
        }
        $csrf = $this->csrfToken->getValue();
        if ($user === null) {
            return $this->jsonResponse->fail('未登录或会话已过期。', Status::UNAUTHORIZED, ['csrf' => $csrf]);
        }
        return $this->jsonResponse->fail('无管理员权限。', Status::FORBIDDEN, ['csrf' => $csrf]);
    }
}
