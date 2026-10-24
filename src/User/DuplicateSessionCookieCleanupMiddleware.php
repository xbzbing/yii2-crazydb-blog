<?php

declare(strict_types=1);

namespace App\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Session\SessionInterface;

/**
 * 修复「后台保存报 CSRF 错误（422 Unprocessable Entity）」的根因中间件。
 *
 * 根因（线上只读探针已复现：单有效 cookie → CSRF 通过 401 guard；陈旧 cookie 排
 * 第一 → 422 与线上失败字节级一致）：
 * - SessionMiddleware 发的 session cookie 带 Domain=request host。用户浏览器里可
 *   并存多个同名 PHPSESSID 槽位（cookie 身份 = name+domain+path，见 RFC6265 §5.3）：
 *   ① apex 域（Domain=crazydb.com，探针证实 https://crazydb.com 会下发）
 *   ② www 域（Domain=www.crazydb.com，SessionMiddleware 正常写入）
 *   ③ legacy host-only（无 Domain，Yii2 时代遗留）
 *   浏览器把全部发给 www，PHP 对同名 cookie 取**第一个**（我的实测：先者胜）。
 * - 当第一个槽位的 session 已被 GC（use_strict_mode=0 下 PHP 接受任意 SID 开出空
 *   session）且用户靠 auth_remember 恢复身份时，RememberMeMiddleware 每次请求都
 *   regenerateId → 新 SID 只通过 Domain=www 的 Set-Cookie 写回（其它槽位永不更新）
 *   → 下个请求又读回陈旧槽位 → 「每请求换 session」死循环：
 *   /me 的 csrf token 落在被丢弃的 session，POST 读到另一个空 session →
 *   `_csrf` 永不匹配 → 必 422。这解释了为何刷新页面（重取 token）也救不回来。
 *
 * 修复（同步收敛，非删除——删除会孤儿化登录当次刚铸入首个槽位的 CSRF token，
 * 反而砸登录流程）：检测到同名 cookie 存在**多个不同值**（真分歧；同值重复无害
 * 且收敛后恒为同值，自动停手）且当前 session 已有登录态时，把**当前活跃 session
 * 的 SID 广播到全部可寻址槽位**（本域域名 cookie + host-only cookie + 父域 cookie，
 * 三者覆盖上列全部变体；www 写 Domain=crazydb.com 合法——域名只需后缀匹配请求域）：
 * - 所有槽位值趋同 → 下个请求 PHP 读到的就是刚处理完本请求的 session
 *   （含 auth 与 `_csrf`）→ token 校验通过；且值一致后 unique<2 → 不再动作，幂等。
 * - 登录成功的那次响应 auth 已写入 → 走广播分支 → 登录当轮即收敛，
 *   对登录表单（GET 铸 token → POST 校验）零干扰。
 * - 无登录态（未登录浏览）不动：登录成功那轮自然收敛，登录前无写操作可保护。
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6265#section-5.3 cookie 身份（name+domain+path）
 * @see vendor/yiisoft/session SessionMiddleware::commitSession（Domain=request host 的来源）
 * @see src/User/RememberMeMiddleware（regenerateId 触发点）
 */
final readonly class DuplicateSessionCookieCleanupMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionInterface $session,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $sessionName = $this->session->getName();
        $cookieHeader = $request->getHeaderLine('Cookie');
        if ($cookieHeader === '' || !\str_contains($cookieHeader, $sessionName . '=')) {
            return $response;
        }

        // 收集同名 cookie 的全部值：≥2 个且存在不同值才是真分歧
        // （同名同值的重复由浏览器对同一 cookie 身份去重，出现即视为无害）
        $matched = \preg_match_all(
            '/(?:^|;\s*)' . \preg_quote($sessionName, '/') . '=([^;]*)/',
            $cookieHeader,
            $m,
        );
        if ($matched === false || $matched < 2 || \count(\array_unique($m[1])) < 2) {
            return $response; // 无重复，或重复但值一致（收敛后的稳态）→ 零干预
        }

        // 未就绪：无登录态不动（登录成功的那轮 auth 已在 session 中，会走广播分支）
        if (!$this->session->isActive()) {
            return $response;
        }
        $sid = $this->session->getId();
        if ($sid === null || $sid === '') {
            return $response;
        }
        if ($this->session->get(User::SESSION_AUTH_KEY) === null) {
            return $response;
        }

        $params = $this->session->getCookieParameters();
        // session_get_cookie_params() 未声明数组形状，取入局部变量再 is_string 收窄
        $rawPath = $params['path'] ?? null;
        $path = \is_string($rawPath) && $rawPath !== '' ? $rawPath : '/';
        $rawSameSite = $params['samesite'] ?? null;
        $sameSite = \is_string($rawSameSite) && $rawSameSite !== '' ? $rawSameSite : 'Lax';
        $common = '; Path=' . $path
            . (!empty($params['httponly']) ? '; HttpOnly' : '')
            . '; SameSite=' . $sameSite
            . (!empty($params['secure']) ? '; Secure' : '');

        $host = $request->getUri()->getHost();

        // 1) 本域域名 cookie（Domain=localhost / Domain=<IP> 会被浏览器拒绝，跳过）
        if (!\filter_var($host, \FILTER_VALIDATE_IP) && \str_contains($host, '.')) {
            $response = $response->withAddedHeader(
                'Set-Cookie',
                $sessionName . '=' . $sid . '; Domain=' . $host . $common,
            );
        }

        // 2) host-only cookie（无 Domain 属性）——覆盖 legacy/无 Domain 变体
        $response = $response->withAddedHeader(
            'Set-Cookie',
            $sessionName . '=' . $sid . $common,
        );

        // 3) 父域 cookie（www.crazydb.com → Domain=crazydb.com）——覆盖 apex 残留
        $parent = $this->parentDomain($host);
        if ($parent !== null) {
            $response = $response->withAddedHeader(
                'Set-Cookie',
                $sessionName . '=' . $sid . '; Domain=' . $parent . $common,
            );
        }

        return $response;
    }

    /**
     * 可写父域 cookie 的父域：www.crazydb.com → crazydb.com。
     * 返回 null 表示不适用（IP、localhost/无点主机、父域是公共后缀形态如 com）。
     */
    private function parentDomain(string $host): ?string
    {
        if ($host === '' || \filter_var($host, \FILTER_VALIDATE_IP) !== false) {
            return null;
        }
        $pos = \strpos($host, '.');
        if ($pos === false || $pos === 0) {
            return null;
        }
        $parent = \substr($host, $pos + 1);
        // 父域必须仍含点（crazydb.com ✓，com ✗），避免对 apex/单段父域误写
        if ($parent === '' || !\str_contains($parent, '.')) {
            return null;
        }
        return $parent;
    }
}
