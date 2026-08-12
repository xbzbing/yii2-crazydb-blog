<?php

declare(strict_types=1);

namespace App\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;
use Yiisoft\Session\SessionInterface;

/**
 * 记住我：请求无 session 登录态但携带有效 token cookie 时恢复身份。
 * 需在 SessionMiddleware 之后挂载；token 校验失败静默（不阻断请求）。
 */
final readonly class RememberMeMiddleware implements MiddlewareInterface
{
    public const COOKIE_NAME = 'auth_remember';
    /** @var int 30 天 */
    public const COOKIE_TTL = 30 * 24 * 3600;

    public function __construct(
        private IdentityWithTokenRepositoryInterface $userRepository,
        private SessionInterface $session,
        private string $sessionKey = User::SESSION_AUTH_KEY,
        private bool $cookieSecure = false,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $current = $this->session->get($this->sessionKey);
        if (is_string($current) && $current !== '') {
            return $handler->handle($request);
        }

        $clearInvalidCookie = false;
        $token = $request->getCookieParams()[self::COOKIE_NAME] ?? null;
        if (is_string($token) && $token !== '') {
            $identity = $this->userRepository->findIdentityByToken($token);
            if ($identity instanceof User) {
                // 必须在下游 guard 执行前恢复身份，否则首次请求仍会被判定未登录。
                $this->session->set($this->sessionKey, (string) $identity->id);
                $this->session->regenerateId();
            } else {
                $clearInvalidCookie = true;
            }
        }

        $response = $handler->handle($request);
        return $clearInvalidCookie
            ? $response->withHeader('Set-Cookie', self::clearCookie($this->cookieSecure))
            : $response;
    }

    /**
     * 构造记住我 cookie 头（Secure 由部署配置控制，https 时须开启）。
     */
    public static function buildCookie(string $token, bool $secure): string
    {
        return self::COOKIE_NAME . '=' . rawurlencode($token)
            . '; Path=/; Max-Age=' . self::COOKIE_TTL
            . '; HttpOnly; SameSite=Lax'
            . ($secure ? '; Secure' : '');
    }

    /**
     * 清除记住我 cookie 头（与 buildCookie 属性一致，避免删除不干净）。
     */
    public static function clearCookie(bool $secure = false): string
    {
        return self::COOKIE_NAME . '=; Path=/; Max-Age=0; HttpOnly; SameSite=Lax'
            . ($secure ? '; Secure' : '');
    }
}
