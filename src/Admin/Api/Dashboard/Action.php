<?php

declare(strict_types=1);

namespace App\Admin\Api\Dashboard;

use App\Admin\Api\JsonResponse;
use App\Comment\Comment;
use App\Option\Option;
use App\Post\Post;
use App\User\User;
use App\Visit\VisitService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/api/dashboard：后台仪表盘统计。
 * 支持 ?days=7|14|30 控制访问趋势周期（默认 14）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private VisitService $visitService,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $days = (int)($request->getQueryParams()['days'] ?? 14);
        $today = $this->visitService->today();

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
            'visitTrend' => $this->visitService->trend($days),
            'visitHourly' => $this->visitService->hourly(24),
        ]);
    }
}
