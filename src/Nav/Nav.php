<?php

declare(strict_types=1);

namespace App\Nav;

use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;

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
        return '{{%nav}}';
    }

    public function getChildren(): ActiveQueryInterface
    {
        return $this->hasMany(self::class, ['pid' => 'id'])->orderBy(['sort_order' => SORT_DESC]);
    }

    /**
     * 访问 URL：route=1 时 url 为路由名，经 UrlGenerator 解析；否则为普通链接。
     * 等价 Yii2 getUrl() 的 [$this->url] 数组形式（Url::to 解析路由）。
     * 路由名不存在时降级输出原文（避免全站 500）。
     * 自定义链接再做一次协议兜底（防存量库中的 javascript:/data: 等危险 scheme 直出）。
     */
    public function getUrl(?UrlGeneratorInterface $urlGenerator = null): string
    {
        if ($this->route === 1 && $urlGenerator !== null) {
            try {
                return $urlGenerator->generate($this->url);
            } catch (\Throwable) {
                // 路由名不存在：降级输出原文，但同样走协议兜底（防存量脏数据直出危险 scheme）
            }
        }
        $cleaned = preg_replace('/[\x00-\x20\x7F]/', '', $this->url) ?? $this->url;
        $scheme = strtolower((string)parse_url($cleaned, PHP_URL_SCHEME));
        return $scheme === '' || in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)
            ? $this->url
            : '#';
    }

    /**
     * 获取父类导航（id => name）。
     *
     * @return array<int, string>
     */
    public static function getParentNav(CacheInterface $cache, bool $refresh = false): array
    {
        $cacheKey = '__parent_nav.' . (int)self::query()->max('update_time');
        if ($refresh) {
            $cache->remove($cacheKey);
        }
        /** @var array<int, string> $items */
        $items = $cache->getOrSet(
            $cacheKey,
            static function (): array {
                $items = [];
                foreach (self::query()->select('id,name')->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->asArray()->all() as $row) {
                    $items[(int)$row['id']] = (string)$row['name'];
                }
                return $items;
            },
            3600,
        );
        return $items;
    }

    /**
     * 获取导航树：顶级节点 + 子节点。
     * 修正 Yii2 原版 bug：子菜单 url 误用父节点 url。
     *
     * @return array<int, array{label: string, url: string, items: array<int, array{label: string, url: string}>}>
     */
    public static function getNavTree(
        CacheInterface $cache,
        ?UrlGeneratorInterface $urlGenerator = null,
        bool $refresh = false,
    ): array {
        $cacheKey = '__nav_tree.' . (int)self::query()->max('update_time');
        if ($refresh) {
            $cache->remove($cacheKey);
        }
        /** @var array<int, array{label: string, url: string, items: array<int, array{label: string, url: string}>}> $items */
        $items = $cache->getOrSet(
            $cacheKey,
            static function () use ($urlGenerator): array {
                $items = [];
                foreach (self::query()->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->all() as $node) {
                    if (!$node instanceof self || $node->id === null) {
                        continue;
                    }
                    $children = [];
                    foreach ($node->getChildren()->all() as $child) {
                        if (!$child instanceof self) {
                            continue;
                        }
                        $children[] = [
                            'label' => $child->name,
                            'url' => $child->getUrl($urlGenerator),
                        ];
                    }
                    $items[$node->id] = [
                        'label' => $node->name,
                        'url' => $node->getUrl($urlGenerator),
                        'items' => $children,
                    ];
                }
                return $items;
            },
            3600,
            null,
        );
        return $items;
    }
}
