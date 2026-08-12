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
    public function __construct(private JsonResponse $jsonResponse)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (stripos($contentType, 'application/json') !== false) {
            $raw = (string) $request->getBody();
            if ($raw === '') {
                $request = $request->withParsedBody([]);
            } else {
                try {
                    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    return $this->jsonResponse->fail('请求体不是有效的 JSON。');
                }
                if (!is_array($data)) {
                    return $this->jsonResponse->fail('JSON 请求体必须为对象或数组。');
                }
                $request = $request->withParsedBody($data);
            }
        }
        return $handler->handle($request);
    }
}
