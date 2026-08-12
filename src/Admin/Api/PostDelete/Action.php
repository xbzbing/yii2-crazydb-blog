<?php

declare(strict_types=1);

namespace App\Admin\Api\PostDelete;

use App\Admin\Api\JsonResponse;
use App\Comment\Comment;
use App\Post\Post;
use App\Tag\Tag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * POST /admin/api/post/delete/{id}：删除文章（连带评论与标签关联）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] int $id,
    ): ResponseInterface {
        $post = Post::query()->findByPk($id);
        if (!$post instanceof Post) {
            return $this->jsonResponse->fail('文章不存在。', 404);
        }
        (new Comment())->deleteAll(['pid' => $id]);
        (new Tag())->deleteAll(['pid' => $id]);
        $post->delete();
        return $this->jsonResponse->ok(['message' => '文章已删除。']);
    }
}
