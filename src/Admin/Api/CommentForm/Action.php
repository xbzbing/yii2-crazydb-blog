<?php

declare(strict_types=1);

namespace App\Admin\Api\CommentForm;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\Comment\Comment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * 后台评论详情/编辑 JSON API：
 * - GET  /admin/api/comment/{id}       详情（回填表单）
 * - POST /admin/api/comment/update/{id} 更新内容/昵称/邮箱/网址/状态
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function detail(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        $comment = Comment::query()->findByPk($id);
        if (!$comment instanceof Comment) {
            return $this->jsonResponse->fail('评论不存在。', 404);
        }
        return $this->jsonResponse->ok(['comment' => ApiSerializer::comment($comment)]);
    }

    public function update(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        $comment = Comment::query()->findByPk($id);
        if (!$comment instanceof Comment) {
            return $this->jsonResponse->fail('评论不存在。', 404);
        }

        $body = $request->getParsedBody();
        $data = is_array($body) ? $body : [];
        $comment->content = trim((string)($data['content'] ?? ''));
        $comment->nickname = trim((string)($data['nickname'] ?? ''));
        $comment->email = trim((string)($data['email'] ?? ''));
        $comment->url = trim((string)($data['url'] ?? '')) ?: null;
        $comment->status = (string)($data['status'] ?? $comment->status);

        if ($comment->content === '') {
            return $this->jsonResponse->ok(['ok' => false, 'errors' => ['content' => '评论内容不能为空。']]);
        }
        if (!in_array($comment->status, [Comment::STATUS_APPROVED, Comment::STATUS_UNAPPROVED, Comment::STATUS_SPAM], true)) {
            return $this->jsonResponse->ok(['ok' => false, 'errors' => ['status' => '状态不合法。']]);
        }

        $comment->update_time = time();
        try {
            $comment->save();
        } catch (\Throwable) {
            return $this->jsonResponse->fail('保存失败。');
        }
        return $this->jsonResponse->ok(['message' => '评论已更新。']);
    }
}
