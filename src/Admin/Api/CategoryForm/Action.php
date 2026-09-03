<?php

declare(strict_types=1);

namespace App\Admin\Api\CategoryForm;

use App\Admin\Api\JsonResponse;
use App\Category\Category;
use App\Common\XUtils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * 后台分类新建/编辑 JSON API。
 * - GET  /admin/api/category/{id}       详情（回填）
 * - POST /admin/api/category/save       新建
 * - POST /admin/api/category/update/{id} 更新
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function detail(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        $category = Category::query()->findByPk($id);
        if (!$category instanceof Category) {
            return $this->jsonResponse->fail('分类不存在。', 404);
        }
        return $this->jsonResponse->ok(['category' => [
            'id' => (int)$category->id,
            'pid' => (int)$category->pid,
            'name' => $category->name,
            'alias' => $category->alias,
            'desc' => (string)$category->desc,
            'keywords' => $category->keywords,
            'sort_order' => (int)$category->sort_order,
            'display' => $category->display,
        ]]);
    }

    public function save(ServerRequestInterface $request): ResponseInterface
    {
        return $this->persist($request, null);
    }

    public function update(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        return $this->persist($request, $id);
    }

    private function persist(ServerRequestInterface $request, ?int $id): ResponseInterface
    {
        /** @var ?Category $category */
        $category = $id !== null ? Category::query()->findByPk($id) : null;
        if ($id !== null && !$category instanceof Category) {
            return $this->jsonResponse->fail('分类不存在。', 404);
        }
        $isNew = $category === null;
        $category ??= new Category();

        $body = $request->getParsedBody();
        $data = is_array($body) ? $body : [];
        $category->name = trim((string)($data['name'] ?? ''));
        $category->alias = trim((string)($data['alias'] ?? ''));
        if ($category->alias === '') {
            $category->alias = XUtils::generateAlias($category->name);
            $base = $category->alias;
            $n = 2;
            while (Category::query()->where(['alias' => $category->alias])->andWhere(['!=', 'id', (int)$category->id])->exists()) {
                $category->alias = $base . '-' . $n++;
            }
        }
        $category->desc = trim((string)($data['desc'] ?? '')) ?: null;
        $category->keywords = trim((string)($data['keywords'] ?? ''));
        $category->sort_order = (int)($data['sort_order'] ?? 0);
        $category->pid = (int)($data['pid'] ?? 0);

        $errors = [];
        if ($category->name === '') {
            $errors['name'] = '分类名称不能为空。';
        }
        if ($category->alias !== '' && Category::query()->where(['alias' => $category->alias])->andWhere(['!=', 'id', (int)$category->id])->exists()) {
            $errors['alias'] = '别名已存在。';
        }
        if ($errors !== []) {
            return $this->jsonResponse->ok(['ok' => false, 'errors' => $errors]);
        }

        $category->desc = $category->desc !== null ? XUtils::htmlPurify($category->desc) : null;
        $category->update_time = time();
        try {
            $category->save();
        } catch (\Throwable) {
            return $this->jsonResponse->fail('保存失败。');
        }

        return $this->jsonResponse->ok([
            'id' => (int)$category->id,
            'message' => $isNew ? '分类已创建。' : '分类已更新。',
        ]);
    }
}
