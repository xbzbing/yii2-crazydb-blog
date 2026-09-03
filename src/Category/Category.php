<?php

declare(strict_types=1);

namespace App\Category;

use App\Post\Post;
use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;

final class Category extends ActiveRecord
{
    public const DISPLAY_LIST = 'list';
    public const DISPLAY_PAGE = 'page';

    public ?int $id = null;
    public string $name = '';
    public string $alias = '';
    public ?string $desc = null;
    public int $pid = 0;
    public string $display = 'list';
    public int $sort_order = 0;
    public string $keywords = '';
    public int $update_time = 0;

    /**
     * 文章变更不会改变分类表的 update_time，因此由文章写入口显式清除该版本键。
     */
    public static function invalidateSummaryCache(CacheInterface $cache): void
    {
        $cache->remove('__category_summary_version');
    }

    public function tableName(): string
    {
        return '{{%category}}';
    }

    public function getChildren(): \Yiisoft\ActiveRecord\ActiveQueryInterface
    {
        return $this->hasMany(self::class, ['pid' => 'id'])->orderBy(['sort_order' => SORT_DESC]);
    }

    /**
     * 分类 URL：alias 走 category/show，无 alias 按 id（对齐 Yii2 强制绝对 URL）。
     */
    public function getUrl(UrlGeneratorInterface $urlGenerator, bool $schema = true): string
    {
        if ($this->alias !== '') {
            return $urlGenerator->generateAbsolute('category/show', ['alias' => $this->alias]);
        }
        return $urlGenerator->generateAbsolute('category/view', ['id' => $this->id]);
    }

    public function getPostCount(): int
    {
        return (int)Post::query()
            ->where(['cid' => $this->id])
            ->andWhere(['in', 'status', Post::visibleStatuses()])
            ->count();
    }

    /**
     * 前台按 alias 取分类（对齐 Yii2 Category::findByAlias）。
     */
    public static function findByAlias(string $alias): ?self
    {
        /** @var ?self $model */
        $model = self::query()->where(['alias' => $alias])->one();
        return $model;
    }

    /**
     * 获取所有的文章分类（id => name）。
     * 等价 Yii2 Category::getAllCategories，DbDependency 用 CallbackDependency 实现。
     *
     * @return array<int, string>
     */
    public static function getAllCategories(CacheInterface $cache, bool $refresh = false): array
    {
        return self::cached($cache, '__categories', $refresh, static function (): array {
            $items = [];
            foreach (self::query()->select('id,name')->asArray()->all() as $row) {
                $items[(int)$row['id']] = (string)$row['name'];
            }
            return $items;
        });
    }

    /**
     * 获取分类概况：id => [name, desc, url, postCount]。
     *
     * @return array<int, array{name: string, desc: ?string, url: ?string, postCount: int}>
     */
    public static function getCategorySummary(
        CacheInterface $cache,
        UrlGeneratorInterface $urlGenerator,
        bool $refresh = false,
    ): array {
        /** @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $summary */
        $summary = self::cached(
            $cache,
            '__category_summary',
            $refresh,
            static function () use ($urlGenerator): array {
            $items = [];
            /** @var list<self> $categories */
            $categories = self::query()->all();
            foreach ($categories as $category) {
                $items[(int)$category->id] = [
                    'name' => $category->name,
                    'desc' => $category->desc,
                    'url' => $category->getUrl($urlGenerator),
                    'postCount' => $category->getPostCount(),
                ];
            }
            return $items;
            },
            (string) $cache->getOrSet(
                '__category_summary_version',
                static fn (): string => bin2hex(random_bytes(16)),
                31536000,
            ),
        );
        return $summary;
    }

    /**
     * @param callable(): array<array-key, mixed> $callback
     * @return array<array-key, mixed>
     */
    private static function cached(
        CacheInterface $cache,
        string $key,
        bool $refresh,
        callable $callback,
        ?string $externalVersion = null,
    ): array {
        $version = (string) (int) self::query()->max('update_time');
        if ($externalVersion !== null) {
            $version .= '.' . $externalVersion;
        }
        $cacheKey = $key . '.' . $version;
        if ($refresh) {
            $cache->remove($cacheKey);
        }
        /** @var array<int, mixed> $value */
        $value = $cache->getOrSet($cacheKey, static fn (): array => $callback(), 3600);
        return $value;
    }
}
