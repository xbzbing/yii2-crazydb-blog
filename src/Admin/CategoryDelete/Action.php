<?php

declare(strict_types=1);

namespace App\Admin\CategoryDelete;

use App\Category\Category;
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
 * 后台删除分类（POST + CSRF，连带失效前台分类缓存）。
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
            $category = Category::query()->findByPk($id);
            if ($category instanceof Category) {
                $this->cache->remove('__category_summary.' . (int)Category::query()->max('update_time'));
                $category->delete();
                $this->cache->remove('__category_summary.' . (int)Category::query()->max('update_time'));
                $this->flash->set('flash_success', ['info' => '分类已删除。']);
            }
        }
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urlGenerator->generate('admin/category/list'));
    }
}
