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
        private FlashInterface $flash,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $name = null,
    ): ResponseInterface {
        if ($name !== null && $request->getMethod() === Method::POST) {
            (new Tag())->deleteAll(['name' => $name]);
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
