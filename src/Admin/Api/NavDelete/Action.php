<?php

declare(strict_types=1);

namespace App\Admin\Api\NavDelete;

use App\Admin\Api\JsonResponse;
use App\Nav\Nav;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * POST /admin/api/nav/delete/{id}：删除导航（连带子导航，失效前台导航缓存）。
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
        $nav = Nav::query()->findByPk($id);
        if (!$nav instanceof Nav) {
            return $this->jsonResponse->fail('导航不存在。', 404);
        }
        $this->cache->remove('__nav_tree.' . (int)Nav::query()->max('update_time'));
        $this->deleteRecursive($id);
        $nav->delete();
        $this->cache->remove('__nav_tree.' . (int)Nav::query()->max('update_time'));
        return $this->jsonResponse->ok(['message' => '导航已删除。']);
    }

    /**
     * 递归删除子导航（含历史三级/孤儿数据，保证树干净）。
     */
    private function deleteRecursive(int $pid): void
    {
        /** @var list<Nav> $children */
        $children = Nav::query()->where(['pid' => $pid])->all();
        foreach ($children as $child) {
            $this->deleteRecursive((int)$child->id);
            $child->delete();
        }
    }
}
