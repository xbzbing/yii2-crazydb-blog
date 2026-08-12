<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Admin\Api\JsonBodyParserMiddleware;
use App\Admin\Api\JsonResponse;
use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonBodyParserMiddlewareTest extends TestCase
{
    public function testInvalidJsonReturnsBadRequestBeforeCallingHandler(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/admin/api/post/save')
            ->withHeader('Content-Type', 'application/json')
            ->withBody((new StreamFactory())->createStream('{invalid'));
        $state = (object) ['called' => false];
        $handler = new class($state) implements RequestHandlerInterface {
            public function __construct(private object $state) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->state->called = true;
                return (new ResponseFactory())->createResponse();
            }
        };

        $middleware = new JsonBodyParserMiddleware(new JsonResponse(new ResponseFactory()));
        $response = $middleware->process($request, $handler);

        self::assertSame(400, $response->getStatusCode());
        self::assertFalse($state->called);
        self::assertSame('请求体不是有效的 JSON。', json_decode((string) $response->getBody(), true)['error']);
    }
}
