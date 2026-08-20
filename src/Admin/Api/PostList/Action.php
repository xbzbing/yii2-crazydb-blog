<?php

declare(strict_types=1);

namespace App\Admin\Api\PostList;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\Category\Category;
use App\Post\Post;
use App\Post\PostViewSyncTrigger;
use App\Tag\Tag;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * GET /admin/api/posts?page=&status=&tag=：文章列表（分页 + 状态/标签过滤）。
 * 读取前触发 on-demand 惰性同步（降频+锁，使 view_count/view_uv 与 Redis 对齐）。
 */
final readonly class Action
{
    private const PAGE_SIZE = 20;
    private const STATUSES = [Post::STATUS_PUBLISHED, Post::STATUS_DRAFT, Post::STATUS_DELETED];
    /** 允许服务端排序的字段白名单 */
    private const SORTABLE_FIELDS = ['view_uv', 'view_count', 'comment_count', 'post_time'];

    public function __construct(
        private JsonResponse $jsonResponse,
        private PostViewSyncTrigger $postViewSyncTrigger,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $page = null,
    ): ResponseInterface {
        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $status = (string)($request->getQueryParams()['status'] ?? '');
        $filtered = in_array($status, self::STATUSES, true);
        $tag = trim((string)($request->getQueryParams()['tag'] ?? ''));
        // on-demand 惰性同步：让列表展示的 view_count/view_uv 尽量贴近 Redis 实时值
        $this->postViewSyncTrigger->trigger();
        // 服务端排序（白名单字段）：默认置顶+发布时间，用户排序时按字段覆盖
        $sortField = (string)($request->getQueryParams()['sort'] ?? '');
        $orderBy = ['is_top' => SORT_DESC, 'post_time' => SORT_DESC];
        if (in_array($sortField, self::SORTABLE_FIELDS, true)) {
            $sortOrder = (string)($request->getQueryParams()['order'] ?? '');
            $orderBy = [$sortField => $sortOrder === 'asc' ? SORT_ASC : SORT_DESC];
        }

        $countQuery = Post::query();
        $query = Post::query();
        if ($filtered) {
            $countQuery->where(['status' => $status]);
            $query->where(['status' => $status]);
        }
        if ($tag !== '') {
            // 标签为关联表：先按名称取文章 id 集合，再过滤主表
            $postIds = Tag::query()->select('pid')->where(['name' => $tag])->column();
            $postIds = array_map('intval', $postIds);
            if ($postIds === []) {
                $postIds = [0];
            }
            $countQuery->andWhere(['in', 'id', $postIds]);
            $query->andWhere(['in', 'id', $postIds]);
        }
        $pager = new Pager((int)$countQuery->count(), self::PAGE_SIZE, $page);

        /** @var list<Post> $posts */
        $posts = $query
            ->orderBy($orderBy)
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        // 分类 id => 名称（列表展示用）
        $categoryNames = [];
        /** @var list<array{id: mixed, name: mixed}> $categoryRows */
        $categoryRows = Category::query()->select('id,name')->asArray()->all();
        foreach ($categoryRows as $cat) {
            $categoryNames[(int)$cat['id']] = (string)$cat['name'];
        }

        return $this->jsonResponse->ok([
            'items' => ApiSerializer::posts($posts, $categoryNames),
            'total' => $pager->totalCount,
            'page' => $pager->currentPage,
            'pageSize' => $pager->pageSize,
            'pageCount' => $pager->pageCount,
            'status' => $status,
            'tag' => $tag,
        ]);
    }
}
