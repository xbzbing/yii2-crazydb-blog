<?php

declare(strict_types=1);

namespace App\Web\CategoryShow;

use App\Category\Category;
use App\Comment\Comment;
use App\Common\CMSUtils;
use App\Nav\Nav;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Tag\Tag;
use App\Web\NotFound\NotFoundResponder;
use App\Web\Pager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 前台分类页：面包屑 + 分类文章分页列表。
 * 等价 Yii2 CategoryController::actionShow。
 */
final readonly class Action
{
    private const PAGE_SIZE = 10;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UrlGeneratorInterface $urlGenerator,
        private ResponseFactoryInterface $responseFactory,
        private CacheInterface $cache,
        private Aliases $aliases,
        private MarkdownRenderer $markdownRenderer,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $alias = null,
        #[RouteArgument] ?int $id = null,
        #[RouteArgument] ?string $page = null,
    ): ResponseInterface {
        $category = $alias !== null
            ? Category::findByAlias($alias)
            : ($id !== null ? Category::query()->findByPk($id) : null);
        if (!$category instanceof Category) {
            return NotFoundResponder::respond($this->viewRenderer, $this->responseFactory, $this->urlGenerator);
        }

        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $siteConfig = CMSUtils::getSiteConfig($this->cache);

        $total = Post::query()
            ->where(['cid' => $category->id, 'status' => Post::visibleStatuses()])
            ->count();
        $pager = new Pager((int)$total, self::PAGE_SIZE, $page);
        $posts = Post::query()
            ->where(['cid' => $category->id, 'status' => Post::visibleStatuses()])
            ->orderBy(['post_time' => SORT_DESC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'category' => $category,
                'posts' => $posts,
                'pager' => $pager,
                'markdownRenderer' => $this->markdownRenderer,
                'urlGenerator' => $this->urlGenerator,
                'siteConfig' => $siteConfig,
                'seoConfig' => CMSUtils::getSiteConfig($this->cache, 'seo'),
                'navTree' => Nav::getNavTree($this->cache, $this->urlGenerator),
                'showSidebar' => true,
                'categorySummary' => Category::getCategorySummary($this->cache, $this->urlGenerator),
                'sidebarTags' => Tag::getTags($this->cache, $this->urlGenerator, false, 20),
                'sidebarComments' => Comment::getRecentComments($this->cache, $this->urlGenerator, $this->aliases, 5),
            ],
        );
    }
}
