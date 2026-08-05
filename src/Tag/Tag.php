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
        $cacheKey = '__tags_' . $limit . '.' . (int)self::query()->max('id');
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
}
