<?php

declare(strict_types=1);

namespace App\Tag;

use Yiisoft\ActiveRecord\ActiveQueryInterface;
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
     * 标签 × 文章统计查询：tag LEFT JOIN post，按标签名分组，文章数只统计
     * 仍存在的文章（COUNT(DISTINCT p.id)，同一文章的重复关联行算 1 篇）。
     * 孤儿 tag 行（文章已删但关联行漏删）计为 0，是否展示由调用方决定。
     */
    private static function postCountQuery(): ActiveQueryInterface
    {
        return self::query()
            ->select(['t.name', 'totalCount' => 'COUNT(DISTINCT p.id)'])
            ->from(['t' => '{{%tag}}'])
            ->leftJoin(['p' => '{{%post}}'], 'p.id = t.pid')
            ->groupBy('t.name');
    }

    /**
     * 按标签统计文章数：只统计 post 表中仍然存在的文章。
     *
     * tag 行可能成为孤儿（文章已删除但关联行漏删、测试残留等），直接 COUNT(tag)
     * 会虚增文章数——曾出现「测试标签x 显示 7 篇、实际 0 篇」，虚增的计数还会把
     * 删除入口一起堵死，垃圾关联行永远清不掉。孤儿标签仍会返回（文章数为 0），
     * 便于后台识别并删除；前台聚合另行过滤掉 0 篇的标签（见 getTags）。
     *
     * @param ?string $name 只统计指定标签；null 表示统计全部标签
     * @return list<array{name: string, totalCount: int}> 文章数降序、标签名升序
     */
    public static function countPostsPerTag(?string $name = null): array
    {
        $query = self::postCountQuery()->orderBy(['totalCount' => SORT_DESC, 't.name' => SORT_ASC]);
        if ($name !== null) {
            $query->where(['t.name' => $name]);
        }
        $items = [];
        /** @var list<array{name: string, totalCount: int|string}> $rows */
        $rows = $query->asArray()->all();
        foreach ($rows as $row) {
            $items[] = ['name' => $row['name'], 'totalCount' => (int)$row['totalCount']];
        }
        return $items;
    }

    /**
     * 指定标签实际关联的文章数（口径同 countPostsPerTag）。
     */
    public static function countPostsByName(string $name): int
    {
        $items = self::countPostsPerTag($name);
        return $items[0]['totalCount'] ?? 0;
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
                $query = self::postCountQuery()
                    ->addSelect(['create_time' => 'MAX(t.create_time)'])
                    ->orderBy(['totalCount' => SORT_DESC])
                    // 前台只展示仍有文章的标签：孤儿 tag 行（文章已删）不得出现
                    ->having('COUNT(DISTINCT p.id) > 0');
                if ($limit > 0) {
                    $query->limit($limit);
                }
                $items = [];
                /** @var list<array{name: string, totalCount: int|string, create_time: int|string}> $rows */
                $rows = $query->asArray()->all();
                foreach ($rows as $row) {
                    $name = $row['name'];
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
