<?php

declare(strict_types=1);

namespace App\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\AuthenticationMethodInterface;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Session\SessionInterface;

/**
 * Session 登录态认证：登录时在 session 写入用户 id，后续请求从 session 恢复身份。
 */
final class SessionAuthMethod implements AuthenticationMethodInterface
{
    public function __construct(
        private SessionInterface $session,
        private UserRepository $userRepository,
        private string $sessionKey = 'authUserId',
    ) {
    }

    public function authenticate(ServerRequestInterface $request): ?IdentityInterface
    {
        $id = $this->session->get($this->sessionKey);
        if (!is_string($id) || $id === '') {
            return null;
        }
        return $this->userRepository->findIdentity($id);
    }

    public function challenge(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
