<?php

declare(strict_types=1);

namespace App\Web\TagShow;

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
 * 前台标签页：面包屑 + 标签文章分页列表。
 * 等价 Yii2 TagController::actionShow。
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
        #[RouteArgument] string $name,
        #[RouteArgument] ?string $page = null,
    ): ResponseInterface {
        $tag = Tag::query()->where(['name' => $name])->one();
        if ($tag === null) {
            return NotFoundResponder::respond($this->viewRenderer, $this->responseFactory, $this->urlGenerator);
        }

        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $siteConfig = CMSUtils::getSiteConfig($this->cache);

        /** @var list<array{pid: int}> $tagRows */
        $tagRows = Tag::query()->select('pid')->where(['name' => $name])->asArray()->all();
        $postIds = array_map(static fn (array $row): int => (int)$row['pid'], $tagRows);
        if ($postIds === []) {
            $postIds = [0];
        }

        $total = Post::query()
            ->where(['id' => $postIds, 'status' => Post::visibleStatuses()])
            ->count();
        $pager = new Pager((int)$total, self::PAGE_SIZE, $page);
        $posts = Post::query()
            ->where(['id' => $postIds, 'status' => Post::visibleStatuses()])
            ->orderBy(['post_time' => SORT_DESC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'tagName' => $name,
                'posts' => $posts,
                'pager' => $pager,
                'markdownRenderer' => $this->markdownRenderer,
                'urlGenerator' => $this->urlGenerator,
                'siteConfig' => $siteConfig,
                'navTree' => Nav::getNavTree($this->cache),
                'showSidebar' => true,
                'categorySummary' => Category::getCategorySummary($this->cache, $this->urlGenerator),
                'sidebarTags' => Tag::getTags($this->cache, $this->urlGenerator, false, 20),
                'sidebarComments' => Comment::getRecentComments($this->cache, $this->urlGenerator, $this->aliases, 5),
            ],
        );
    }
}
