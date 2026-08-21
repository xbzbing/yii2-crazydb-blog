<?php

declare(strict_types=1);

namespace App\Admin\NavForm;

use App\Nav\Nav;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\RouteCollectionInterface;

/**
 * 导航保存共享逻辑（HTML 后台与 JSON API 双入口复用）：
 * 赋值、校验（名称/URL 协议/父级约束）、保存、清缓存。
 */
final readonly class NavFormService
{
    public function __construct(
        private RouteCollectionInterface $routeCollection,
        private CacheInterface $cache,
    ) {
    }

    /**
     * 校验并保存导航。
     *
     * @param array<array-key, mixed> $data HTTP parsedBody（键实际为 string，但 psalm 只能推断为 array-key）
     * @return array{ok: bool, nav?: Nav, errors?: array<string, string>, message?: string}
     */
    public function save(array $data, ?int $id = null): array
    {
        /** @var ?Nav $nav */
        $nav = $id !== null ? Nav::query()->findByPk($id) : null;
        if ($id !== null && !$nav instanceof Nav) {
            return ['ok' => false, 'errors' => ['id' => '导航不存在。']];
        }
        $isNew = $nav === null;
        $nav ??= new Nav();

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
        } elseif (!self::isSafeCustomUrl($nav->url)) {
            $errors['url'] = '自定义链接协议不合法（仅支持 http/https/mailto/tel 或站内相对路径）。';
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
            return ['ok' => false, 'nav' => $nav, 'errors' => $errors];
        }

        $now = time();
        if ($isNew) {
            $nav->create_time = $now;
        }
        $nav->update_time = $now;
        try {
            $nav->save();
        } catch (\Throwable) {
            return ['ok' => false, 'nav' => $nav, 'errors' => ['save' => '保存失败。']];
        }
        $this->cache->remove('__nav_tree.' . (int)Nav::query()->max('update_time'));

        return ['ok' => true, 'nav' => $nav, 'message' => $isNew ? '导航已创建。' : '导航已更新。'];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function parents(): array
    {
        /** @var list<Nav> $parents */
        $parents = Nav::query()->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->all();
        return array_map(static fn (Nav $n): array => ['id' => (int)$n->id, 'name' => $n->name], $parents);
    }

    /**
     * 自定义链接（route=0）协议校验：剥离控制字符（浏览器解析 href 时会
     * 先去除 ASCII 制表/换行，原始 newline 可把 `java\nscript:` 折叠成
     * javascript:），再按协议白名单拒绝 javascript:/data:/vbscript:/file: 等危险 scheme。
     */
    public static function isSafeCustomUrl(string $url): bool
    {
        $cleaned = preg_replace('/[\x00-\x20\x7F]/', '', $url) ?? $url;
        $scheme = strtolower((string)parse_url($cleaned, PHP_URL_SCHEME));
        return $scheme === '' || in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
    }
}
