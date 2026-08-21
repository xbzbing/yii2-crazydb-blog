<?php

declare(strict_types=1);

namespace App\Admin\Api\LogList;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\Log\Log;
use App\User\User;
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
        $result = (string)($request->getQueryParams()['result'] ?? '');
        $ip = (string)($request->getQueryParams()['ip'] ?? '');
        $userAgent = (string)($request->getQueryParams()['user_agent'] ?? '');

        $conditions = [];
        if ($type !== '') {
            $conditions[] = ['type' => $type];
        }
        if ($result !== '') {
            $conditions[] = ['result' => $result];
        }
        if ($ip !== '') {
            $conditions[] = ['ip' => $ip];
        }
        if ($userAgent !== '') {
            $conditions[] = ['like', 'user_agent', $userAgent];
        }

        $countQuery = Log::query();
        foreach ($conditions as $condition) {
            $countQuery->andWhere($condition);
        }
        $pager = new Pager((int)$countQuery->count(), self::PAGE_SIZE, $page);

        $query = Log::query();
        foreach ($conditions as $condition) {
            $query->andWhere($condition);
        }
        /** @var list<Log> $logs */
        $logs = $query
            ->orderBy(['create_time' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        // 批量预取 uid → 昵称映射（避免逐条查询）
        $uids = array_values(array_unique(array_map(static fn (Log $l): int => (int)$l->uid, $logs)));
        $nicknameMap = [];
        if ($uids !== []) {
            /** @var list<User> $users */
            $users = User::query()->where(['in', 'id', $uids])->all();
            foreach ($users as $user) {
                $nicknameMap[(int)$user->id] = $user->nickname !== '' ? $user->nickname : $user->username;
            }
        }

        /** @var list<array{type: string}> $typeRows */
        $typeRows = Log::query()->select('type')->distinct()->asArray()->all();

        return $this->jsonResponse->ok([
            'items' => ApiSerializer::logs($logs, $nicknameMap),
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
        $oneYearAgo = (new \DateTimeImmutable('-1 year'))->getTimestamp();
        $deleted = (new Log())->deleteAll(['<', 'create_time', $oneYearAgo]);
        return $this->jsonResponse->ok([
            'message' => '已清理 1 年前的日志。',
            'deleted' => $deleted,
        ]);
    }
}
