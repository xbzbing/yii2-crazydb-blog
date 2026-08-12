<?php

declare(strict_types=1);

namespace App\Admin\UserList;

use App\User\User;
use App\Web\Pager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台用户管理：列表 + 禁用/启用（等价 Yii2 admin UserController::actionIndex）。
 */
final readonly class Action
{
    private const PAGE_SIZE = 20;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $page = null,
        #[RouteArgument] ?string $action = null,
        #[RouteArgument] ?int $id = null,
    ): ResponseInterface {
        if ($id !== null && $request->getMethod() === Method::POST) {
            return $this->toggle($action, $id);
        }

        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $pager = new Pager((int)User::query()->count(), self::PAGE_SIZE, $page);
        /** @var list<User> $users */
        $users = User::query()
            ->orderBy(['id' => SORT_ASC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                ['users' => $users, 'pager' => $pager],
            );
    }

    private function toggle(?string $action, int $id): ResponseInterface
    {
        $user = User::query()->findByPk($id);
        if ($user instanceof User && $user->username !== 'dabing') {
            if ($action === 'ban' && $user->status !== User::STATUS_BANED) {
                $user->status = User::STATUS_BANED;
                $user->update_time = time();
                $user->save();
                $this->flash->set('flash_success', ['info' => '用户已禁用。']);
            } elseif ($action === 'unban' && $user->status === User::STATUS_BANED) {
                $user->status = User::STATUS_NORMAL;
                $user->update_time = time();
                $user->save();
                $this->flash->set('flash_success', ['info' => '用户已启用。']);
            }
        }
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('admin/user/list'));
    }
}
