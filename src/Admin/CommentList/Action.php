<?php

declare(strict_types=1);

namespace App\Admin\CommentList;

use App\Comment\Comment;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台评论管理（列表 + 状态筛选，等价 Yii2 admin CommentController::actionIndex）。
 */
final readonly class Action
{
    private const PAGE_SIZE = 20;

    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $page = null,
    ): ResponseInterface {
        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $status = (string)($request->getQueryParams()['status'] ?? '');
        $statuses = [Comment::STATUS_APPROVED, Comment::STATUS_UNAPPROVED, Comment::STATUS_SPAM];
        $filtered = in_array($status, $statuses, true);

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

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                [
                    'comments' => $comments,
                    'pager' => $pager,
                    'status' => $status,
                ],
            );
    }
}
