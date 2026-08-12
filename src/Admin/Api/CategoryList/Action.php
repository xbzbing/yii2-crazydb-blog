<?php

declare(strict_types=1);

namespace App\Admin\Api\CategoryList;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\Category\Category;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/api/categories：分类列表（全量）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<Category> $categories */
        $categories = Category::query()->orderBy(['sort_order' => SORT_DESC])->all();
        return $this->jsonResponse->ok(['items' => ApiSerializer::categories($categories)]);
    }
}
