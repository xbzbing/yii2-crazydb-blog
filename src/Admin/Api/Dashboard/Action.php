<?php

declare(strict_types=1);

namespace App\Admin\Api\Dashboard;

use App\Admin\Api\JsonResponse;
use App\Comment\Comment;
use App\Option\Option;
use App\Post\Post;
use App\Post\PostViewSyncTrigger;
use App\User\User;
use App\Visit\VisitService;
use App\Visit\VisitSyncTrigger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/api/dashboard：后台仪表盘统计。
 * 支持 ?days=7|14|30 控制访问趋势周期（默认 14）。
 * 读取前触发 on-demand 惰性同步（文章浏览 + 站点日统计，降频+锁）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private VisitService $visitService,
        private PostViewSyncTrigger $postViewSyncTrigger,
        private VisitSyncTrigger $visitSyncTrigger,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // on-demand 惰性同步：仪表盘刷新时把 Redis 统计增量落库（降频+锁，与 cron 并存）
        $this->postViewSyncTrigger->trigger();
        $this->visitSyncTrigger->trigger();

        $days = (int)($request->getQueryParams()['days'] ?? 14);
        $today = $this->visitService->today();
        $yesterday = $this->visitService->yesterday();

        return $this->jsonResponse->ok([
            'postTotal' => (int)Post::query()->count(),
            'commentTotal' => (int)Comment::query()->count(),
            'pendingComments' => (int)Comment::query()->where(['status' => Comment::STATUS_UNAPPROVED])->count(),
            'userTotal' => (int)User::query()->count(),
            'optionTotal' => (int)Option::query()->count(),
            'todayPv' => $today['pv'],
            'todayUv' => $today['uv'],
            'todayIp' => $today['ip'],
            'todayCrawler' => $today['pv_crawler'],
            'todayScript' => $today['pv_script'],
            'todayNormal' => $today['pv_normal'],
            'todayNotFoundPv' => $today['notfound_pv'],
            'todayNotFoundUv' => $today['notfound_uv'],
            'yesterdayPv' => $yesterday['pv'],
            'yesterdayUv' => $yesterday['uv'],
            'yesterdayIp' => $yesterday['ip'],
            'yesterdayNotFoundPv' => $yesterday['notfound_pv'],
            'yesterdayNotFoundUv' => $yesterday['notfound_uv'],
            'visitTrend' => $this->visitService->trend($days),
            'visitHourly' => $this->visitService->hourly(24),
        ]);
    }
}
