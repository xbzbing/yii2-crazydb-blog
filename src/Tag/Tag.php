<?php

declare(strict_types=1);

namespace App\Tag;

use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\CallbackDependency;
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
        return 'tag';
    }

    /**
     * 标签 URL（相对，对齐 Yii2 getTags 内生成方式）。
     */
    public static function getUrl(UrlGeneratorInterface $urlGenerator, string $name): string
    {
        return $urlGenerator->generate('tag/show', ['name' => $name]);
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
        $key = '__tags_' . $limit;
        if ($refresh) {
            $cache->remove($key);
        }
        return $cache->getOrSet(
            $key,
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
            new CallbackDependency(
                static fn (): int => (int)self::query()->max('id'),
            ),
        );
    }
}
