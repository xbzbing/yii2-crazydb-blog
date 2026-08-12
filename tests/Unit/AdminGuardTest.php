<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Admin\AdminGuardMiddleware;
use App\Tests\TestCase;
use App\User\User;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * 后台守卫鉴权矩阵：未登录 / 非管理员 / 管理员。
 */
final class AdminGuardTest extends TestCase
{
    private function middleware(): AdminGuardMiddleware
    {
        return new AdminGuardMiddleware(
            $this->middlewareAuth(),
            new ResponseFactory(),
            $this->container()->get(UrlGeneratorInterface::class),
        );
    }

    private function middlewareAuth(): \App\User\AuthService
    {
        return new \App\User\AuthService($this->sharedSession(), new \App\User\UserRepository());
    }

    private function handler(): \Psr\Http\Server\RequestHandlerInterface
    {
        return new class() implements \Psr\Http\Server\RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                return (new ResponseFactory())->createResponse(200);
            }
        };
    }

    public function testGuestRedirectedToLogin(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin');
        $response = $this->middleware()->process($request, $this->handler());
        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('/login', (string)$response->getHeaderLine('Location'));
    }

    public function testMemberRedirectedToLogin(): void
    {
        $suffix = 'guard_' . bin2hex(random_bytes(3));
        $user = new User();
        $user->username = 'member_' . $suffix;
        $user->nickname = '成员' . $suffix;
        $user->email = $suffix . '@example.com';
        $user->password = 'password123';
        $user->role = User::ROLE_MEMBER;
        $user->status = User::STATUS_NORMAL;
        $user->fillDefaultsForInsert();
        $user->save();
        try {
            $this->middlewareAuth()->login($user);
            $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin');
            $response = $this->middleware()->process($request, $this->handler());
            self::assertSame(302, $response->getStatusCode(), 'member must be rejected');
        } finally {
            $user->delete();
            $this->middlewareAuth()->logout();
        }
    }

    public function testAdminPasses(): void
    {
        $suffix = 'guard_' . bin2hex(random_bytes(3));
        $user = new User();
        $user->username = 'admin_' . $suffix;
        $user->nickname = '管理员' . $suffix;
        $user->email = $suffix . '@example.com';
        $user->password = 'password123';
        $user->role = User::ROLE_ADMIN;
        $user->status = User::STATUS_NORMAL;
        $user->fillDefaultsForInsert();
        $user->save();
        try {
            $this->middlewareAuth()->login($user);
            $request = (new ServerRequestFactory())->createServerRequest('GET', '/admin');
            $response = $this->middleware()->process($request, $this->handler());
            self::assertSame(200, $response->getStatusCode(), 'admin must pass');
        } finally {
            $user->delete();
            $this->middlewareAuth()->logout();
        }
    }
}
