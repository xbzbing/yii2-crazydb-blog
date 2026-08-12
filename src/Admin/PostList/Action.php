<?php

declare(strict_types=1);

namespace App\Admin\PostList;

use App\Post\Post;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台文章列表（分页 + 状态过滤）。
 */
final readonly class Action
{
    private const PAGE_SIZE = 20;
    private const STATUSES = [Post::STATUS_PUBLISHED, Post::STATUS_DRAFT, Post::STATUS_DELETED];

    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

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

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                [
                    'posts' => $posts,
                    'pager' => $pager,
                    'status' => $status,
                ],
            );
    }
}
