<?php

declare(strict_types=1);

namespace App\Admin\Api\CommentAction;

use App\Admin\Api\JsonResponse;
use App\Comment\Comment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * POST /admin/api/comment/{action}/{id}：通过审核 / 删除评论。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] string $action,
        #[RouteArgument] int $id,
    ): ResponseInterface {
        $comment = Comment::query()->findByPk($id);
        if (!$comment instanceof Comment) {
            return $this->jsonResponse->fail('评论不存在。', 404);
        }
        if ($action === 'approve' && $comment->status !== Comment::STATUS_APPROVED) {
            $comment->status = Comment::STATUS_APPROVED;
            $comment->update_time = time();
            $comment->save();
            return $this->jsonResponse->ok(['message' => '评论已通过审核。']);
        }
        if ($action === 'delete') {
            $comment->delete();
            return $this->jsonResponse->ok(['message' => '评论已删除。']);
        }
        return $this->jsonResponse->fail('不支持的操作。', 422);
    }
}
