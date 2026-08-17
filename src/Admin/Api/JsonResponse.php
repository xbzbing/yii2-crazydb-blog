<?php

declare(strict_types=1);

namespace App\Admin\Api;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Status;

/**
 * 后台 JSON API 统一响应构造器。
 *
 * 约定包体：{"ok":bool,"data":mixed,"error":?string,"csrf":?string}
 * - ok=true  表示操作成功，data 携带数据
 * - ok=false 表示失败，error 携带人类可读错误（前端 message.error）
 * - csrf 在 GET /admin/api/me 时返回当前同步器 token（SPA 后续写操作经 X-CSRF-Token header 提交）
 */
final readonly class JsonResponse
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function ok(mixed $data = null, int $status = Status::OK): ResponseInterface
    {
        return $this->json(['ok' => true, 'data' => $data, 'error' => null], $status);
    }

    /**
     * @param array<string, mixed> $extra 额外字段（如未登录时附带 csrf token）
     */
    public function fail(string $error, int $status = Status::BAD_REQUEST, array $extra = []): ResponseInterface
    {
        return $this->json(['ok' => false, 'data' => null, 'error' => $error] + $extra, $status);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $status): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(
            (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
        return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }
}
