<?php

declare(strict_types=1);

namespace App\Tag;

use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;

final class Tag extends ActiveRecord
{
    public ?int $id = null;
    public string $name = '';
    public int $pid = 0;
    public int $cid = 0;
    public int $create_time = 0;

    public function tableName(): string
    {
        return '{{%tag}}';
    }

    /**
     * 标签 URL（相对，对齐 Yii2 getTags 内生成方式）。
     */
    public static function getUrl(UrlGeneratorInterface $urlGenerator, string $name): string
    {
        return $urlGenerator->generate('tag/show', ['name' => $name]);
    }

    /**
     * 同步文章标签：先删后插（等价 Yii2 Tag::post2tags）。
     * 逗号/中文逗号/空格分割、去重、限 5 个、仅允许字母数字下划线中文。
     */
    public static function post2tags(string $tags, int $pid, int $cid): int
    {
        $tagsArray = array_unique(explode(',', str_replace([' ', '，'], ',', $tags)));
        $tagCount = 0;
        (new self())->deleteAll(['pid' => $pid]);
        foreach ($tagsArray as $tag) {
            if ($tagCount >= 5 || !preg_match('/^[0-9a-zA-Z_\x{4e00}-\x{9fa5}]+$/u', $tag)) {
                continue;
            }
            $one = new self();
            $one->name = $tag;
            $one->pid = $pid;
            $one->cid = $cid;
            try {
                $one->save();
                $tagCount++;
            } catch (\Throwable) {
            }
        }
        return $tagCount;
    }

    /**
     * 使所有标签聚合缓存失效。文章保存和删除都必须调用此方法，避免仅删除记录时最大 ID 不变。
     */
    public static function invalidateCache(CacheInterface $cache): void
    {
        $cache->remove('__tags_version');
    }

    /**
     * 标签统计：按名称聚合计数，按计数降序；等价 Yii2 Tag::getTags。
     *
     * @return list<array{totalCount: int, name: string, create_time: int, url: string}>
     */
    public static function getTags(
        CacheInterface $cache,
        UrlGeneratorInterface $urlGenerator,
        bool $refresh = false,
        int $limit = 0,
    ): array {
        $version = $cache->getOrSet(
            '__tags_version',
            static fn (): string => bin2hex(random_bytes(16)),
            31536000,
        );
        $cacheKey = '__tags_' . $limit . '.' . $version;
        if ($refresh) {
            $cache->remove($cacheKey);
        }
        /** @var list<array{totalCount: int, name: string, create_time: int, url: string}> $items */
        $items = $cache->getOrSet(
            $cacheKey,
            static function () use ($urlGenerator, $limit): array {
                $query = self::query()
                    ->select('name, COUNT(*) as totalCount, MAX(create_time) as create_time')
                    ->groupBy('name')
                    ->orderBy(['totalCount' => SORT_DESC]);
                if ($limit > 0) {
                    $query->limit($limit);
                }
                $items = [];
                foreach ($query->asArray()->all() as $row) {
                    $name = (string)$row['name'];
                    $items[] = [
                        'totalCount' => (int)$row['totalCount'],
                        'name' => $name,
                        'create_time' => (int)$row['create_time'],
                        'url' => self::getUrl($urlGenerator, $name),
                    ];
                }
                return $items;
            },
            3600,
        );
        return $items;
    }

    /**
     * 删除标签（按名称）：同时清理各文章冗余的 tags 字符串中的该标签，
     * 避免前台按标签访问出现 404（标签关联行已删但 post.tags 残留）。
     */
    public static function deleteByName(string $name, \Yiisoft\Cache\CacheInterface $cache): void
    {
        (new self())->deleteAll(['name' => $name]);

        // 清理 post.tags 冗余字符串（"a,b,c" → 去掉 name，逗号规整）
        $db = (new self())->db();
        $rows = $db->createCommand(
            'SELECT id, tags FROM {{%post}} WHERE tags LIKE :tag',
            [':tag' => '%' . $name . '%'],
        )->queryAll();
        foreach ($rows as $row) {
            $tags = array_values(array_filter(
                array_map('trim', explode(',', (string)$row['tags'])),
                static fn (string $t): bool => $t !== '' && $t !== $name,
            ));
            $clean = implode(',', $tags);
            if ($clean !== (string)$row['tags']) {
                $db->createCommand(
                    'UPDATE {{%post}} SET tags = :tags WHERE id = :id',
                    [':tags' => $clean, ':id' => (int)$row['id']],
                )->execute();
            }
        }

        self::invalidateCache($cache);
    }
}
