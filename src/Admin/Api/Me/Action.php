<?php

declare(strict_types=1);

namespace App\Admin\Api\Me;

use App\Admin\Api\JsonResponse;
use App\User\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Csrf\CsrfTokenInterface;

/**
 * GET /admin/api/me：返回当前登录管理员信息 + 同步器 CSRF token。
 * SPA 首屏调用一次，缓存 token 后所有写操作经 X-CSRF-Token header 提交。
 */
final readonly class Action
{
    public function __construct(
        private AuthService $authService,
        private CsrfTokenInterface $csrfToken,
        private JsonResponse $jsonResponse,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->authService->currentUser();
        return $this->jsonResponse->ok([
            'user' => [
                'id' => (int)$user?->id,
                'username' => (string)$user?->username,
                'nickname' => (string)$user?->nickname,
                'avatar' => (string)$user?->avatar,
                'email' => (string)$user?->email,
                'role' => (int)$user?->role,
            ],
            'csrf' => $this->csrfToken->getValue(),
        ]);
    }
}
