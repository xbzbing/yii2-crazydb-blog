<?php

declare(strict_types=1);

namespace App\Admin\NavDelete;

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

/**
 * 后台删除导航（POST + CSRF，连带删除子导航，失效前台导航缓存）。
 */
final readonly class Action
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] int $id,
    ): ResponseInterface {
        if ($request->getMethod() === Method::POST) {
            $nav = Nav::query()->findByPk($id);
            if ($nav instanceof Nav) {
                $this->cache->remove('__nav_tree.' . (int)Nav::query()->max('update_time'));
                $this->deleteRecursive($id);
                $nav->delete();
                $this->cache->remove('__nav_tree.' . (int)Nav::query()->max('update_time'));
                $this->flash->set('flash_success', ['info' => '导航已删除。']);
            }
        }
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('admin/nav/list'));
    }

    /**
     * 递归删除子导航（含历史三级/孤儿数据，保证树干净）。
     */
    private function deleteRecursive(int $pid): void
    {
        /** @var list<Nav> $children */
        $children = Nav::query()->where(['pid' => $pid])->all();
        foreach ($children as $child) {
            $this->deleteRecursive((int)$child->id);
            $child->delete();
        }
    }
}
