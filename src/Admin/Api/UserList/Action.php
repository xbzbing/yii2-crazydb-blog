<?php

declare(strict_types=1);

namespace App\Admin\Api\UserList;

use App\Admin\Api\ApiSerializer;
use App\Admin\Api\JsonResponse;
use App\User\User;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * 后台用户 JSON API：
 * - GET  /admin/api/users?page=                  分页列表
 * - POST /admin/api/user/{action}/{id}           禁用/启用（ban/unban，站长 dabing 保护）
 */
final readonly class Action
{
    private const PAGE_SIZE = 20;

    public function __construct(
        private JsonResponse $jsonResponse,
    ) {
    }

    public function list(ServerRequestInterface $request, #[RouteArgument] ?string $page = null): ResponseInterface
    {
        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $keyword = trim((string)($request->getQueryParams()['keyword'] ?? ''));
        $countQuery = User::query();
        if ($keyword !== '') {
            $countQuery->andWhere(['like', 'username', $keyword])
                ->orWhere(['like', 'nickname', $keyword])
                ->orWhere(['like', 'email', $keyword]);
        }
        $pager = new Pager((int)$countQuery->count(), self::PAGE_SIZE, $page);

        $query = User::query();
        if ($keyword !== '') {
            $query->where(['like', 'username', $keyword])
                ->orWhere(['like', 'nickname', $keyword])
                ->orWhere(['like', 'email', $keyword]);
        }
        /** @var list<User> $users */
        $users = $query
            ->orderBy(['id' => SORT_ASC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        return $this->jsonResponse->ok([
            'items' => ApiSerializer::users($users),
            'total' => $pager->totalCount,
            'page' => $pager->currentPage,
            'pageSize' => $pager->pageSize,
            'pageCount' => $pager->pageCount,
        ]);
    }

    public function toggle(ServerRequestInterface $request, #[RouteArgument] string $action, #[RouteArgument] int $id): ResponseInterface
    {
        $user = User::query()->findByPk($id);
        if (!$user instanceof User) {
            return $this->jsonResponse->fail('用户不存在。', 404);
        }
        if ($user->username === 'dabing') {
            return $this->jsonResponse->fail('站长账号不可操作。', 422);
        }
        if ($action === 'ban' && $user->status !== User::STATUS_BANED) {
            $user->status = User::STATUS_BANED;
            $user->update_time = time();
            $user->save();
            return $this->jsonResponse->ok(['message' => '用户已禁用。']);
        }
        if ($action === 'unban' && $user->status === User::STATUS_BANED) {
            $user->status = User::STATUS_NORMAL;
            $user->update_time = time();
            $user->save();
            return $this->jsonResponse->ok(['message' => '用户已启用。']);
        }
        return $this->jsonResponse->ok(['message' => '无需操作。']);
    }
}
