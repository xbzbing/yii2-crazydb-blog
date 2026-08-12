<?php

declare(strict_types=1);

namespace App\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Session\SessionInterface;

/**
 * 记住我：请求无 session 登录态但携带有效 token cookie 时恢复身份。
 * 需在 SessionMiddleware 之后挂载；token 校验失败静默（不阻断请求）。
 */
final readonly class RememberMeMiddleware implements MiddlewareInterface
{
    public const COOKIE_NAME = 'auth_remember';
    public const COOKIE_TTL = 30 * 24 * 3600;

    public function __construct(
        private UserRepository $userRepository,
        private SessionInterface $session,
        private string $sessionKey = User::SESSION_AUTH_KEY,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $current = $this->session->get($this->sessionKey);
        if (is_string($current) && $current !== '') {
            return $handler->handle($request);
        }

        $token = $request->getCookieParams()[self::COOKIE_NAME] ?? null;
        if (is_string($token) && $token !== '') {
            $identity = $this->userRepository->findIdentityByToken($token);
            if ($identity instanceof User) {
                $this->session->set($this->sessionKey, (string)$identity->id);
            }
        }

        return $handler->handle($request);
    }
}
