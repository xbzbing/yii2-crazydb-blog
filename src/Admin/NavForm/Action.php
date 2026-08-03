<?php

declare(strict_types=1);

namespace App\Admin\NavForm;

use App\Nav\Nav;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 后台导航新建/编辑（等价 Yii2 admin NavController::actionCreate/actionUpdate）。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?int $id = null,
    ): ResponseInterface {
        /** @var ?Nav $nav */
        $nav = $id !== null ? Nav::query()->findByPk($id) : null;
        if ($id !== null && $nav === null) {
            return $this->redirectList();
        }
        $isNew = $nav === null;
        $nav ??= new Nav();
        $errors = [];

        /** @var list<Nav> $parents */
        $parents = Nav::query()->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->all();

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
            $data = is_array($body) ? $body : [];
            $nav->name = trim((string)($data['name'] ?? ''));
            $nav->url = trim((string)($data['url'] ?? ''));
            $nav->route = (int)($data['route'] ?? 0) > 0 ? 1 : 0;
            $nav->pid = (int)($data['pid'] ?? 0);
            $nav->sort_order = (int)($data['sort_order'] ?? 0);

            if ($nav->name === '') {
                $errors['name'] = '导航名称不能为空。';
            }
            if ($nav->url === '') {
                $errors['url'] = 'URL 或路由名不能为空。';
            }
            if ($nav->pid !== 0 && !Nav::query()->where(['id' => $nav->pid, 'pid' => 0])->exists()) {
                $errors['pid'] = '父导航不存在或不是顶级导航（仅支持两级）。';
            }
            if ($nav->pid !== 0 && $nav->id !== null && $nav->pid === (int)$nav->id) {
                $errors['pid'] = '父导航不能是自身。';
            }

            if ($errors === []) {
                $now = time();
                if ($isNew) {
                    $nav->create_time = $now;
                }
                $nav->update_time = $now;
                try {
                    $nav->save();
                } catch (\Throwable) {
                    $errors['save'] = '保存失败。';
                }
                if ($errors === []) {
                    $this->cache->remove('__nav_tree.' . (int)Nav::query()->max('update_time'));
                    $this->flash->set('flash_success', ['info' => $isNew ? '导航已创建。' : '导航已更新。']);
                    return $this->redirectList();
                }
            }
        }

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                ['nav' => $nav, 'isNew' => $isNew, 'parents' => $parents, 'errors' => $errors],
            );
    }

    private function redirectList(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('admin/nav/list'));
    }
}
