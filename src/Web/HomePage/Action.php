<?php

declare(strict_types=1);

namespace App\Web\HomePage;

use App\Category\Category;
use App\Comment\Comment;
use App\Common\CMSUtils;
use App\Nav\Nav;
use App\Option\Option;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Tag\Tag;
use App\Web\Pager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
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

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument] ?string $page = null,
    ): ResponseInterface {
        $page = (int)($page ?? $request->getQueryParams()['page'] ?? 1);
        $siteConfig = CMSUtils::getSiteConfig($this->cache);

        // 维护模式：首页渲染维护页（title 维护中 + 维护文案）
        if (($siteConfig[Option::SITE_STATUS] ?? Option::STATUS_RUNNING) === Option::STATUS_MAINTENANCE) {
            return $this->viewRenderer->renderPartial(
                __DIR__ . '/maintenance',
                [
                    'siteConfig' => $siteConfig,
                    'maintenanceMessage' => (string)($siteConfig[Option::MAINTENANCE_MESSAGE] ?? Option::MAINTENANCE_MESSAGE_DEFAULT),
                    'urlGenerator' => $this->urlGenerator,
                ],
            );
        }

        $total = Post::query()
            ->where(['status' => Post::visibleStatuses()])
            ->count();
        $pager = new Pager((int)$total, self::PAGE_SIZE, $page);
        $posts = Post::query()
            ->where(['status' => Post::visibleStatuses()])
            ->orderBy(['is_top' => SORT_DESC, 'post_time' => SORT_DESC])
            ->limit(self::PAGE_SIZE)
            ->offset($pager->offset)
            ->all();

        // 栏目分区数据（墨刊主题：按分类取最新 3 篇；版本化 key 缓存，仅首页计算）
        $sections = $page === 1 ? $this->loadSections() : [];
        /** @var ?Post $latest */
        $latest = $page === 1 ? ($posts[0] ?? null) : null;
        $latestId = $latest?->id;

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'posts' => $posts,
                'latest' => $latest,
                'latestId' => $latestId,
                'sections' => $sections,
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

    /**
     * 栏目分区：顶级分类各取最新 3 篇（版本化 key 缓存，避免每请求 N+1）。
     *
     * @return list<array{category: Category, posts: list<Post>}>
     */
    private function loadSections(): array
    {
        /** @var list<array{category: Category, posts: list<Post>}> $sections */
        $sections = $this->cache->getOrSet(
            '__section_posts.' . (int)Post::query()->max('update_time'),
            static function (): array {
                $sections = [];
                /** @var list<Category> $topCategories */
                $topCategories = Category::query()->where(['pid' => 0])->orderBy(['sort_order' => SORT_DESC])->limit(3)->all();
                foreach ($topCategories as $category) {
                    $childIds = Category::query()
                        ->select('id')
                        ->where(['pid' => (int)$category->id])
                        ->column();
                    $cids = array_merge([(int)$category->id], $childIds);
                    /** @var list<Post> $sectionPosts */
                    $sectionPosts = Post::query()
                        ->select('id,title,alias,cid,post_time')
                        ->where(['cid' => $cids, 'status' => Post::visibleStatuses()])
                        ->orderBy(['post_time' => SORT_DESC])
                        ->limit(3)
                        ->all();
                    $sections[] = [
                        'category' => $category,
                        'posts' => $sectionPosts,
                    ];
                }
                return $sections;
            },
            3600,
        );
        return $sections;
    }
}
