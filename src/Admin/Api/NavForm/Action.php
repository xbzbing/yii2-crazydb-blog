<?php

declare(strict_types=1);

namespace App\Admin\Api\NavForm;

use App\Admin\Api\JsonResponse;
use App\Nav\Nav;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\RouteCollectionInterface;

/**
 * 后台导航新建/编辑 JSON API。
 * - GET  /admin/api/nav/{id}       详情 + 顶级父导航下拉
 * - POST /admin/api/nav/save       新建
 * - POST /admin/api/nav/update/{id} 更新
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private RouteCollectionInterface $routeCollection,
        private CacheInterface $cache,
    ) {
    }

    public function detail(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        $nav = Nav::query()->findByPk($id);
        if (!$nav instanceof Nav) {
            return $this->jsonResponse->fail('导航不存在。', 404);
        }
        return $this->jsonResponse->ok([
            'nav' => [
                'id' => (int)$nav->id,
                'pid' => (int)$nav->pid,
                'name' => $nav->name,
                'url' => $nav->url,
                'route' => (int)$nav->route,
                'sort_order' => (int)$nav->sort_order,
            ],
            'parents' => $this->parents(),
        ]);
    }

    public function save(ServerRequestInterface $request): ResponseInterface
    {
        return $this->persist($request, null);
    }

    public function update(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        return $this->persist($request, $id);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function parents(): array
    {
        /** @var list<Nav> $parents */
        $parents = Nav::query()->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->all();
        return array_map(static fn (Nav $n): array => ['id' => (int)$n->id, 'name' => $n->name], $parents);
    }

    private function persist(ServerRequestInterface $request, ?int $id): ResponseInterface
    {
        /** @var ?Nav $nav */
        $nav = $id !== null ? Nav::query()->findByPk($id) : null;
        if ($id !== null && !$nav instanceof Nav) {
            return $this->jsonResponse->fail('导航不存在。', 404);
        }
        $isNew = $nav === null;
        $nav ??= new Nav();

        $body = $request->getParsedBody();
        $data = is_array($body) ? $body : [];
        $nav->name = trim((string)($data['name'] ?? ''));
        $nav->url = trim((string)($data['url'] ?? ''));
        $nav->route = (int)($data['route'] ?? 0) > 0 ? 1 : 0;
        $nav->pid = (int)($data['pid'] ?? 0);
        $nav->sort_order = (int)($data['sort_order'] ?? 0);

        $errors = [];
        if ($nav->name === '') {
            $errors['name'] = '导航名称不能为空。';
        }
        if ($nav->url === '') {
            $errors['url'] = 'URL 或路由名不能为空。';
        } elseif ($nav->route === 1) {
            try {
                $this->routeCollection->getRoute($nav->url);
            } catch (\Throwable) {
                $errors['url'] = '路由名不存在（如 post/list、site/index）。';
            }
        }
        if ($nav->pid !== 0 && !Nav::query()->where(['id' => $nav->pid, 'pid' => 0])->exists()) {
            $errors['pid'] = '父导航不存在或不是顶级导航（仅支持两级）。';
        }
        if ($nav->pid !== 0 && $nav->id !== null && $nav->pid === (int)$nav->id) {
            $errors['pid'] = '父导航不能是自身。';
        }
        if ($nav->pid !== 0 && $nav->id !== null
            && Nav::query()->where(['pid' => (int)$nav->id])->exists()
        ) {
            $errors['pid'] = '该导航存在子导航，不能降级为子级。';
        }
        if ($errors !== []) {
            return $this->jsonResponse->ok(['ok' => false, 'errors' => $errors]);
        }

        $now = time();
        if ($isNew) {
            $nav->create_time = $now;
        }
        $nav->update_time = $now;
        try {
            $nav->save();
        } catch (\Throwable) {
            return $this->jsonResponse->fail('保存失败。');
        }
        $this->cache->remove('__nav_tree.' . (int)Nav::query()->max('update_time'));

        return $this->jsonResponse->ok([
            'id' => (int)$nav->id,
            'message' => $isNew ? '导航已创建。' : '导航已更新。',
        ]);
    }
}
