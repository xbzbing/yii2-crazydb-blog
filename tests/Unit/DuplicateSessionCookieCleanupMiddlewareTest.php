<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use App\User\DuplicateSessionCookieCleanupMiddleware;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Session\SessionInterface;

/**
 * 双 PHPSESSID cookie（apex/www/legacy host-only 槽位并存）导致线上保存必 422 的
 * 修复中间件：检测同名 cookie 多值分歧后，把当前活跃 session 的 SID 广播到
 * 全部可寻址槽位（本域域名 + host-only + 父域），使各槽位值趋同、token 校验恢复。
 *
 * 依据（线上只读探针）：单有效 cookie → CSRF 通过（401 guard）；陈旧 cookie 排第一
 * → 422 Unprocessable Entity（与线上失败字节级一致）。
 */
final class DuplicateSessionCookieCleanupMiddlewareTest extends TestCase
{
    private function handler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse(200);
            }
        };
    }

    /** @param array<string, mixed> $sessionOverrides */
    private function sessionMock(array $sessionOverrides = []): SessionInterface
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('getName')->willReturn('PHPSESSID');
        $session->method('getCookieParameters')->willReturn([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $options = $sessionOverrides + [
            'active' => true,
            'id' => 'aabbccddeeff00112233445566778899',
            'auth' => 1,
        ];
        $session->method('isActive')->willReturn($options['active']);
        $session->method('getId')->willReturn($options['id']);
        $session->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null) => $key === 'authUserId' ? $options['auth'] : $default,
        );
        return $session;
    }

    public function testDivergentCookiesTriggerSidBroadcastToAllSlots(): void
    {
        $middleware = new DuplicateSessionCookieCleanupMiddleware($this->sessionMock());
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://www.crazydb.com/admin/api/me')
            ->withHeader('Cookie', 'PHPSESSID=sta1eold000000000000000000000001; PHPSESSID=aabbccddeeff00112233445566778899');

        $response = $middleware->process($request, $this->handler());
        $cookies = $response->getHeader('Set-Cookie');

        self::assertCount(3, $cookies, '分歧 + 已登录 → 广播到 本域域名/host-only/父域 三个槽位');
        // 本域域名 cookie
        self::assertStringContainsString('PHPSESSID=aabbccddeeff00112233445566778899; Domain=www.crazydb.com', $cookies[0]);
        // host-only cookie（无 Domain 属性）
        self::assertStringContainsString('PHPSESSID=aabbccddeeff00112233445566778899; Path=/', $cookies[1]);
        self::assertStringNotContainsString('Domain=', $cookies[1]);
        // 父域 cookie：覆盖 apex 残留
        self::assertStringContainsString('PHPSESSID=aabbccddeeff00112233445566778899; Domain=crazydb.com', $cookies[2]);
        // 均为会话级 cookie（无 Max-Age），HttpOnly/SameSite 与原 cookie 对齐
        foreach ($cookies as $cookie) {
            self::assertStringContainsString('HttpOnly', $cookie);
            self::assertStringContainsString('SameSite=Lax', $cookie);
            self::assertStringNotContainsString('Max-Age', $cookie);
        }
    }

    public function testSingleCookieUntouched(): void
    {
        $middleware = new DuplicateSessionCookieCleanupMiddleware($this->sessionMock());
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://www.crazydb.com/admin/api/me')
            ->withHeader('Cookie', 'PHPSESSID=aabbccddeeff00112233445566778899');

        $response = $middleware->process($request, $this->handler());
        self::assertSame([], $response->getHeader('Set-Cookie'), '单 cookie 用户零干预');
    }

    public function testNoSessionCookieHeaderUntouched(): void
    {
        $middleware = new DuplicateSessionCookieCleanupMiddleware($this->sessionMock());
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://www.crazydb.com/');

        $response = $middleware->process($request, $this->handler());
        self::assertSame([], $response->getHeader('Set-Cookie'));
    }

    public function testDuplicatedButIdenticalValuesUntouched(): void
    {
        // 收敛后的稳态：多个槽位同值 → 无分歧，零干预（幂等停手）
        $middleware = new DuplicateSessionCookieCleanupMiddleware($this->sessionMock());
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://www.crazydb.com/admin/api/me')
            ->withHeader('Cookie', 'PHPSESSID=aabbccddeeff00112233445566778899; PHPSESSID=aabbccddeeff00112233445566778899');

        $response = $middleware->process($request, $this->handler());
        self::assertSame([], $response->getHeader('Set-Cookie'));
    }

    public function testUnauthenticatedSessionUntouched(): void
    {
        // 无登录态不动：登录成功的那轮 auth 已写入 session，会走广播分支收敛；
        // 登录前的浏览没有写操作可保护，避免干扰登录表单的 token 铸造
        $middleware = new DuplicateSessionCookieCleanupMiddleware($this->sessionMock(['auth' => null]));
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://www.crazydb.com/admin/api/me')
            ->withHeader('Cookie', 'PHPSESSID=staleold0000000000000000000000012; PHPSESSID=aabbccddeeff00112233445566778899');

        $response = $middleware->process($request, $this->handler());
        self::assertSame([], $response->getHeader('Set-Cookie'));
    }

    public function testApexHostBroadcastsDomainAndHostOnlyWithoutParent(): void
    {
        // apex 视角：父域是公共后缀形态（com）不写；本域域名 + host-only 仍广播
        $middleware = new DuplicateSessionCookieCleanupMiddleware($this->sessionMock());
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://crazydb.com/')
            ->withHeader('Cookie', 'PHPSESSID=xxx; PHPSESSID=aabbccddeeff00112233445566778899');

        $response = $middleware->process($request, $this->handler());
        $cookies = $response->getHeader('Set-Cookie');
        self::assertCount(2, $cookies);
        self::assertStringContainsString('Domain=crazydb.com', $cookies[0]);
        self::assertStringNotContainsString('Domain=', $cookies[1]);
    }

    public function testIpHostOnlyWritesHostOnlyCookie(): void
    {
        // IP 主机：域名/IP 形式的 Domain 会被浏览器拒绝 → 只写 host-only
        $middleware = new DuplicateSessionCookieCleanupMiddleware($this->sessionMock());
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'http://127.0.0.1:8080/')
            ->withHeader('Cookie', 'PHPSESSID=xxx; PHPSESSID=yyy');

        $response = $middleware->process($request, $this->handler());
        $cookies = $response->getHeader('Set-Cookie');
        self::assertCount(1, $cookies);
        self::assertStringNotContainsString('Domain=', $cookies[0]);
    }
}
