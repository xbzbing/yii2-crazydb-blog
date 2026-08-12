<?php

declare(strict_types=1);

namespace App\Nav;

use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\ActiveQuery;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\CallbackDependency;

final class Nav extends ActiveRecord
{
    public ?int $id = null;
    public int $pid = 0;
    public string $name = '';
    public string $url = '';
    public int $route = 0;
    public int $sort_order = 0;
    public ?string $extra = null;
    public int $create_time = 0;
    public int $update_time = 0;

    public function tableName(): string
    {
        return 'nav';
    }

    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(self::class, ['pid' => 'id'])->orderBy(['sort_order' => SORT_DESC]);
    }

    /**
     * 访问 URL：route=1 时 url 为路由名（阶段 E 由 UrlGenerator 解析）。
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * 获取父类导航（id => name）。
     *
     * @return array<int, string>
     */
    public static function getParentNav(CacheInterface $cache, bool $refresh = false): array
    {
        if ($refresh) {
            $cache->remove('__parent_nav');
        }
        return $cache->getOrSet(
            '__parent_nav',
            static function (): array {
                $items = [];
                foreach (self::query()->select('id,name')->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->asArray()->all() as $row) {
                    $items[(int)$row['id']] = (string)$row['name'];
                }
                return $items;
            },
            3600,
            new CallbackDependency(
                static fn(): int => (int)self::query()->max('update_time'),
            ),
        );
    }

    /**
     * 获取导航树：顶级节点 + 子节点。
     * 修正 Yii2 原版 bug：子菜单 url 误用父节点 url。
     *
     * @return array<int, array{label: string, url: string, items: array<int, array{label: string, url: string}>}>
     */
    public static function getNavTree(CacheInterface $cache, bool $refresh = false): array
    {
        if ($refresh) {
            $cache->remove('__nav_tree');
        }
        return $cache->getOrSet(
            '__nav_tree',
            static function (): array {
                $items = [];
                foreach (self::query()->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->all() as $node) {
                    $children = [];
                    foreach ($node->getChildren()->all() as $child) {
                        $children[] = [
                            'label' => $child->name,
                            'url' => $child->getUrl(),
                        ];
                    }
                    $items[$node->id] = [
                        'label' => $node->name,
                        'url' => $node->getUrl(),
                        'items' => $children,
                    ];
                }
                return $items;
            },
            3600,
            new CallbackDependency(
                static fn(): int => (int)self::query()->max('update_time'),
            ),
        );
    }
}
