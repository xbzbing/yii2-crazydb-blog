<?php

declare(strict_types=1);

namespace App\Admin\Api\NavForm;

use App\Admin\Api\JsonResponse;
use App\Admin\NavForm\NavFormService;
use App\Nav\Nav;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * 后台导航新建/编辑 JSON API。
 * - GET  /admin/api/nav/{id}       详情 + 顶级父导航下拉
 * - POST /admin/api/nav/save       新建
 * - POST /admin/api/nav/update/{id} 更新
 * 校验/保存/清缓存逻辑见 NavFormService（与 HTML 后台双入口共享）。
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private NavFormService $service,
    ) {
    }

    public function detail(ServerRequestInterface $request, #[RouteArgument] int $id): ResponseInterface
    {
        $nav = Nav::query()->findByPk($id);
        if (!$nav instanceof Nav) {
            return $this->jsonResponse->fail('导航不存在。', 404);
        }
        return $this->jsonResponse->ok([
            'nav' => [
                'id' => (int)$nav->id,
                'pid' => (int)$nav->pid,
                'name' => $nav->name,
                'url' => $nav->url,
                'route' => (int)$nav->route,
                'sort_order' => (int)$nav->sort_order,
            ],
            'parents' => $this->service->parents(),
        ]);
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
        $body = $request->getParsedBody();
        $data = is_array($body) ? $body : [];
        $result = $this->service->save($data, $id);

        if (!$result['ok']) {
            if (isset($result['errors']['id'])) {
                return $this->jsonResponse->fail('导航不存在。', 404);
            }
            return $this->jsonResponse->ok(['ok' => false, 'errors' => $result['errors'] ?? []]);
        }

        return $this->jsonResponse->ok([
            'id' => $result['nav'] instanceof Nav ? (int)$result['nav']->id : null,
            'message' => $result['message'] ?? '',
        ]);
    }
}
