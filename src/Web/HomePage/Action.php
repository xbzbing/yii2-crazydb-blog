<?php

declare(strict_types=1);

namespace App\Web\HomePage;

use App\Category\Category;
use App\Comment\Comment;
use App\Common\CMSUtils;
use App\Nav\Nav;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Tag\Tag;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class Action
{
    private const PAGE_SIZE = 10;

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private Aliases $aliases,
        private MarkdownRenderer $markdownRenderer,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $page = max(1, (int)($request->getQueryParams()['page'] ?? 1));
        $siteConfig = CMSUtils::getSiteConfig($this->cache);

        $total = Post::query()
            ->where(['status' => Post::visibleStatuses()])
            ->count();
        $pager = new Pager((int)$total, self::PAGE_SIZE, $page);
        $posts = Post::query()
            ->where(['status' => Post::visibleStatuses()])
            ->orderBy(['post_time' => SORT_DESC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'posts' => $posts,
                'pager' => $pager,
                'markdownRenderer' => $this->markdownRenderer,
                'urlGenerator' => $this->urlGenerator,
                'siteConfig' => $siteConfig,
                'navTree' => Nav::getNavTree($this->cache),
                'showSidebar' => true,
                'categorySummary' => Category::getCategorySummary($this->cache),
                'sidebarTags' => Tag::getTags($this->cache, $this->urlGenerator, false, 20),
                'sidebarComments' => Comment::getRecentComments($this->cache, $this->urlGenerator, $this->aliases, 5),
            ],
        );
    }
}
