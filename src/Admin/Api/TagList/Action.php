<?php

declare(strict_types=1);

namespace App\Admin\Api\TagList;

use App\Admin\Api\JsonResponse;
use App\Tag\Tag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * 后台标签 JSON API：
 * - GET  /admin/api/tags                聚合列表（按名称计数）
 * - POST /admin/api/tag/delete/{name}   删除标签（连带文章关联行，失效前台缓存）
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private CacheInterface $cache,
    ) {
    }

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        // 文章数只统计仍存在的文章（countPostsPerTag 内部按 post 关联聚合），
        // 避免孤儿 tag 行虚增数量（见 Tag::countPostsPerTag 注释）
        return $this->jsonResponse->ok(['items' => Tag::countPostsPerTag()]);
    }

    public function delete(ServerRequestInterface $request, #[RouteArgument] string $name): ResponseInterface
    {
        $totalCount = Tag::countPostsByName($name);
        if ($totalCount > 0) {
            return $this->jsonResponse->fail(
                sprintf('该标签关联 %d 篇文章，无法删除；请先在文章中移除该标签。', $totalCount),
                422,
            );
        }
        Tag::deleteByName($name, $this->cache);
        return $this->jsonResponse->ok(['message' => '标签已删除。']);
    }
}
