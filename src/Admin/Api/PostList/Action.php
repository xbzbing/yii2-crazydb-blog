<?php

declare(strict_types=1);

namespace App\Admin\Api\PostList;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\Post\Post;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * GET /admin/api/posts?page=&status=：文章列表（分页 + 状态过滤）。
 */
final readonly class Action
{
    private const PAGE_SIZE = 20;
    private const STATUSES = [Post::STATUS_PUBLISHED, Post::STATUS_HIDDEN, Post::STATUS_DRAFT, Post::STATUS_DELETED];

    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $page = null,
    ): ResponseInterface {
        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $status = (string)($request->getQueryParams()['status'] ?? '');
        $filtered = in_array($status, self::STATUSES, true);

        $countQuery = Post::query();
        if ($filtered) {
            $countQuery->where(['status' => $status]);
        }
        $pager = new Pager((int)$countQuery->count(), self::PAGE_SIZE, $page);

        $query = Post::query();
        if ($filtered) {
            $query->where(['status' => $status]);
        }
        /** @var list<Post> $posts */
        $posts = $query
            ->orderBy(['post_time' => SORT_DESC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        return $this->jsonResponse->ok([
            'items' => ApiSerializer::posts($posts),
            'total' => $pager->totalCount,
            'page' => $pager->currentPage,
            'pageSize' => $pager->pageSize,
            'pageCount' => $pager->pageCount,
            'status' => $status,
        ]);
    }
}
