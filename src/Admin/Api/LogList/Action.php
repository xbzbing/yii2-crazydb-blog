<?php

declare(strict_types=1);

namespace App\Admin\Api\LogList;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\Log\Log;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * 后台日志 JSON API：
 * - GET  /admin/api/logs?page=&type=   分页列表（类型筛选）
 * - POST /admin/api/logs/clear         清空日志
 */
final readonly class Action
{
    private const PAGE_SIZE = 20;

    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function list(ServerRequestInterface $request, #[RouteArgument] ?string $page = null): ResponseInterface
    {
        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $type = (string)($request->getQueryParams()['type'] ?? '');

        $countQuery = Log::query();
        if ($type !== '') {
            $countQuery->where(['type' => $type]);
        }
        $pager = new Pager((int)$countQuery->count(), self::PAGE_SIZE, $page);

        $query = Log::query();
        if ($type !== '') {
            $query->where(['type' => $type]);
        }
        /** @var list<Log> $logs */
        $logs = $query
            ->orderBy(['create_time' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        /** @var list<array{type: string}> $typeRows */
        $typeRows = Log::query()->select('type')->distinct()->asArray()->all();

        return $this->jsonResponse->ok([
            'items' => ApiSerializer::logs($logs),
            'types' => array_column($typeRows, 'type'),
            'total' => $pager->totalCount,
            'page' => $pager->currentPage,
            'pageSize' => $pager->pageSize,
            'pageCount' => $pager->pageCount,
            'type' => $type,
        ]);
    }

    public function clear(ServerRequestInterface $request): ResponseInterface
    {
        (new Log())->deleteAll();
        return $this->jsonResponse->ok(['message' => '日志已清空。']);
    }
}
