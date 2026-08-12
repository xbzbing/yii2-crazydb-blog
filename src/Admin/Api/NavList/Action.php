<?php

declare(strict_types=1);

namespace App\Admin\Api\NavList;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\Nav\Nav;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /admin/api/navs：导航列表（平铺，前端自组树）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<Nav> $navs */
        $navs = Nav::query()->orderBy(['sort_order' => SORT_DESC, 'id' => SORT_ASC])->all();
        return $this->jsonResponse->ok(['items' => ApiSerializer::navs($navs)]);
    }
}
