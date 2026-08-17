<?php

declare(strict_types=1);

namespace App\Admin\Dashboard;

use App\Comment\Comment;
use App\Option\Option;
use App\Post\Post;
use App\User\User;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台仪表盘：核心统计。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                [
                    'postTotal' => (int)Post::query()->count(),
                    'commentTotal' => (int)Comment::query()->count(),
                    'pendingComments' => (int)Comment::query()->where(['status' => Comment::STATUS_UNAPPROVED])->count(),
                    'userTotal' => (int)User::query()->count(),
                    'optionTotal' => (int)Option::query()->count(),
                ],
            );
    }
}
