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
        /** @var list<array{name: string, totalCount: int}> $tags */
        $tags = Tag::query()
            ->select('name, COUNT(*) as totalCount')
            ->groupBy('name')
            ->orderBy(['totalCount' => SORT_DESC])
            ->asArray()
            ->all();
        return $this->jsonResponse->ok(['items' => $tags]);
    }

    public function delete(ServerRequestInterface $request, #[RouteArgument] string $name): ResponseInterface
    {
        $this->cache->remove('__tags_0.' . (int)Tag::query()->max('id'));
        $this->cache->remove('__tags_20.' . (int)Tag::query()->max('id'));
        (new Tag())->deleteAll(['name' => $name]);
        $this->cache->remove('__tags_0.' . (int)Tag::query()->max('id'));
        $this->cache->remove('__tags_20.' . (int)Tag::query()->max('id'));
        return $this->jsonResponse->ok(['message' => '标签已删除。']);
    }
}
