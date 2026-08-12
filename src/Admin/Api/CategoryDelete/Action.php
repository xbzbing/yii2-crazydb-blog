<?php

declare(strict_types=1);

namespace App\Admin\Api\CategoryDelete;

use App\Admin\Api\JsonResponse;
use App\Category\Category;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * POST /admin/api/category/delete/{id}：删除分类（失效前台分类缓存）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private CacheInterface $cache,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] int $id,
    ): ResponseInterface {
        $category = Category::query()->findByPk($id);
        if (!$category instanceof Category) {
            return $this->jsonResponse->fail('分类不存在。', 404);
        }
        $this->cache->remove('__category_summary.' . (int)Category::query()->max('update_time'));
        $this->cache->remove('__categories.' . (int)Category::query()->max('update_time'));
        $category->delete();
        $this->cache->remove('__category_summary.' . (int)Category::query()->max('update_time'));
        $this->cache->remove('__categories.' . (int)Category::query()->max('update_time'));
        return $this->jsonResponse->ok(['message' => '分类已删除。']);
    }
}
