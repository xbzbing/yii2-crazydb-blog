<?php

declare(strict_types=1);

namespace App\Admin\Api\Dashboard;

use App\Admin\Api\JsonResponse;
use App\Comment\Comment;
use App\Option\Option;
use App\Post\Post;
use App\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/api/dashboard：后台仪表盘统计。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->jsonResponse->ok([
            'postTotal' => (int)Post::query()->count(),
            'commentTotal' => (int)Comment::query()->count(),
            'pendingComments' => (int)Comment::query()->where(['status' => Comment::STATUS_UNAPPROVED])->count(),
            'userTotal' => (int)User::query()->count(),
            'optionTotal' => (int)Option::query()->count(),
        ]);
    }
}
