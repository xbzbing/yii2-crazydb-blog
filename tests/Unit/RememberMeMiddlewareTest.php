<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\User\RememberMeMiddleware;
use App\User\User;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;
use Yiisoft\Session\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
final class RememberMeMiddlewareTest extends TestCase
{
    public function testRestoresSessionBeforeCallingTheNextHandler(): void
    {
        $user = new User();
        $user->id = 42;
        $repository = $this->createMock(IdentityWithTokenRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findIdentityByToken')
            ->with('valid-token')
            ->willReturn($user);

        $values = [];
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(
            static function (string $key, mixed $default = null) use (&$values): mixed {
                return $values[$key] ?? $default;
            },
        );
        $session->method('set')->willReturnCallback(
            static function (string $key, mixed $value) use (&$values): void {
                $values[$key] = $value;
            },
        );
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/admin')
            ->withCookieParams([RememberMeMiddleware::COOKIE_NAME => 'valid-token']);
        $handler = new class($session) implements RequestHandlerInterface {
            public bool $sawRestoredSession = false;

            public function __construct(private readonly SessionInterface $session)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->sawRestoredSession = $this->session->get(User::SESSION_AUTH_KEY) === '42';
                return (new ResponseFactory())->createResponse();
            }
        };

        (new RememberMeMiddleware($repository, $session))->process($request, $handler);

        self::assertTrue($handler->sawRestoredSession);
    }
}
