<?php

declare(strict_types=1);

namespace App\Admin\Api\CommentList;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\Comment\Comment;
use App\Post\Post;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * GET /admin/api/comments?page=&status=：评论列表（分页 + 状态过滤，附带被评论文章信息）。
 */
final readonly class Action
{
    private const PAGE_SIZE = 20;
    private const STATUSES = [Comment::STATUS_APPROVED, Comment::STATUS_UNAPPROVED, Comment::STATUS_SPAM];

    public function __construct(
        private JsonResponse $jsonResponse,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $page = null,
    ): ResponseInterface {
        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $status = (string)($request->getQueryParams()['status'] ?? '');
        $filtered = in_array($status, self::STATUSES, true);

        $countQuery = Comment::query();
        if ($filtered) {
            $countQuery->where(['status' => $status]);
        }
        $pager = new Pager((int)$countQuery->count(), self::PAGE_SIZE, $page);

        $query = Comment::query();
        if ($filtered) {
            $query->where(['status' => $status]);
        }
        /** @var list<Comment> $comments */
        $comments = $query
            ->orderBy(['create_time' => SORT_DESC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        // 批量预取被评论文章（避免逐条 getPost 查询，消除 N+1）
        $pids = array_values(array_unique(array_map(static fn (Comment $c): int => (int)$c->pid, $comments)));
        $postMap = [];
        if ($pids !== []) {
            /** @var list<Post> $postRows */
            $postRows = Post::query()->where(['in', 'id', $pids])->all();
            foreach ($postRows as $post) {
                $postMap[(int)$post->id] = $post;
            }
        }

        $items = [];
        foreach ($comments as $comment) {
            $item = ApiSerializer::comment($comment);
            $post = $postMap[(int)$comment->pid] ?? null;
            if ($post instanceof Post) {
                $item['post_id'] = (int)$post->id;
                $item['post_title'] = $post->title;
                $item['post_url'] = $post->getUrl($this->urlGenerator);
            } else {
                $item['post_id'] = null;
                $item['post_title'] = null;
                $item['post_url'] = null;
            }
            $items[] = $item;
        }

        return $this->jsonResponse->ok([
            'items' => $items,
            'total' => $pager->totalCount,
            'page' => $pager->currentPage,
            'pageSize' => $pager->pageSize,
            'pageCount' => $pager->pageCount,
            'status' => $status,
        ]);
    }
}
