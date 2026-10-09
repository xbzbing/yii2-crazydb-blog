<?php

declare(strict_types=1);

namespace App\Admin\Api\PostPreview;

use App\Admin\Api\JsonResponse;
use App\Category\Category;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * GET /admin/api/post/{id}/preview：文章后台预览。
 *
 * 正文渲染复用前台同一管线（Post::getContentProcessed → MarkdownRenderer）：
 * - markdown 文章：commonmark(GFM) 渲染 + HTMLPurifier 净化；
 * - html 老文章：仅净化直出（不经过 markdown 转换）。
 * 返回净化后的 HTML，前端 modal 直接展示。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private MarkdownRenderer $markdownRenderer,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        $post = Post::query()->findByPk($id);
        if (!$post instanceof Post) {
            return $this->jsonResponse->fail('文章不存在。', 404);
        }

        $categoryName = '';
        if ($post->cid > 0) {
            $category = Category::query()->findByPk($post->cid);
            if ($category instanceof Category) {
                $categoryName = (string)$category->name;
            }
        }

        return $this->jsonResponse->ok([
            'post' => [
                'id' => (int)$post->id,
                'title' => $post->title,
                'author_name' => $post->author_name,
                'category_name' => $categoryName,
                'status' => $post->status,
                'format' => $post->format,
                'tags' => $post->tags,
                'cover' => (string)$post->cover,
                'is_top' => (int)$post->is_top,
                'is_locked' => $post->password !== null && $post->password !== '',
                'post_time' => (int)$post->post_time,
                'comment_count' => (int)$post->comment_count,
                'view_count' => (int)$post->view_count,
            ],
            'html' => $post->getContentProcessed($this->markdownRenderer),
        ]);
    }
}