<?php

declare(strict_types=1);

namespace App\Admin\TagList;

use App\Tag\Tag;
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
 * 后台标签管理：聚合列表（按名称计数）+ 删除标签（连带文章关联行）。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private \Yiisoft\Cache\CacheInterface $cache,
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $name = null,
    ): ResponseInterface {
        if ($name !== null && $request->getMethod() === Method::POST) {
            // 仅允许删除无关联文章的标签，避免前台按标签 404
            $totalCount = (int)Tag::query()->where(['name' => $name])->count();
            if ($totalCount > 0) {
                $this->flash->set('flash_error', ['info' => sprintf('该标签关联 %d 篇文章，无法删除；请先在文章中移除该标签。', $totalCount)]);
                return $this->responseFactory
                    ->createResponse(Status::FOUND)
                    ->withHeader('Location', $this->urlGenerator->generate('admin/tag/list'));
            }
            Tag::deleteByName($name, $this->cache);
            $this->flash->set('flash_success', ['info' => '标签已删除。']);
            return $this->responseFactory
                ->createResponse(Status::FOUND)
                ->withHeader('Location', $this->urlGenerator->generate('admin/tag/list'));
        }

        /** @var list<array{name: string, totalCount: int}> $tags */
        $tags = Tag::query()
            ->select('name, COUNT(*) as totalCount')
            ->groupBy('name')
            ->orderBy(['totalCount' => SORT_DESC])
            ->asArray()
            ->all();

        return $this->viewRenderer
            ->withLayout('@src/Web/Shared/Layout/Admin/layout.php')
            ->render(
                __DIR__ . '/template',
                ['tags' => $tags],
            );
    }
}
