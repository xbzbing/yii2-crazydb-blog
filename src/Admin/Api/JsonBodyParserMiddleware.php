<?php

declare(strict_types=1);

namespace App\Admin\Api;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * JSON 请求体解析中间件：当 Content-Type 为 application/json 时，
 * 读取原始 body 并 json_decode 写入 parsedBody（HttpSoft 默认只解析 form/multipart）。
 */
final readonly class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (stripos($contentType, 'application/json') !== false) {
            $raw = (string)$request->getBody();
            $data = json_decode($raw, true);
            if (is_array($data)) {
                $request = $request->withParsedBody($data);
            } elseif ($raw === '') {
                $request = $request->withParsedBody([]);
            }
        }
        return $handler->handle($request);
    }
}
