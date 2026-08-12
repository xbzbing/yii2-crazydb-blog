<?php

declare(strict_types=1);

namespace App\Admin\Api\CustomConfigList;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\CustomConfig\CustomConfig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 自定义配置列表 JSON API：
 * - GET /admin/api/custom-configs/categories  分类分组（category + 记录数），默认视图
 * - GET /admin/api/custom-configs?category=&search=  指定分类记录（category/name/key 模糊搜索）
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function categories(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<array{category: string, cnt: int}> $rows */
        $rows = CustomConfig::query()
            ->select('category, COUNT(*) AS cnt')
            ->groupBy('category')
            ->orderBy(['category' => SORT_ASC])
            ->asArray()
            ->all();
        $items = [];
        foreach ($rows as $row) {
            $items[] = ['category' => (string)$row['category'], 'count' => (int)$row['cnt']];
        }
        return $this->jsonResponse->ok(['items' => $items]);
    }

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $category = trim((string)($request->getQueryParams()['category'] ?? ''));
        $search = trim((string)($request->getQueryParams()['search'] ?? ''));

        $query = CustomConfig::query();
        if ($category !== '' || $search !== '') {
            $conditions = [];
            if ($category !== '') {
                $conditions['category'] = $category;
            }
            if ($search !== '') {
                // category AND (name LIKE ? OR key LIKE ? OR category LIKE ?)
                $query->where($conditions);
                $query->andWhere(
                    ['or', ['like', 'name', $search], ['like', 'key', $search], ['like', 'category', $search]],
                );
            } else {
                $query->where($conditions);
            }
        }
        /** @var list<CustomConfig> $configs */
        $configs = $query->orderBy(['priority' => SORT_DESC, 'id' => SORT_DESC])->all();

        return $this->jsonResponse->ok(['items' => ApiSerializer::customConfigs($configs)]);
    }
}
