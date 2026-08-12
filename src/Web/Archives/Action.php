<?php

declare(strict_types=1);

namespace App\Web\Archives;

use App\Category\Category;
use App\Comment\Comment;
use App\Common\CMSUtils;
use App\Nav\Nav;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Tag\Tag;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * 文章归档页：按年月分组列出全部可见文章。
 * 等价 Yii2 PostController::actionArchives。
 */
final readonly class Action
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private Aliases $aliases,
        private MarkdownRenderer $markdownRenderer,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        $version = (int)Post::query()->max('update_time');
        /** @var array<string, list<Post>> $grouped */
        $grouped = $this->cache->getOrSet(
            '__archives.' . $version,
            static function (): array {
                /** @var list<Post> $posts */
                $posts = Post::query()
                    ->select('id,title,alias,post_time')
                    ->where(['status' => Post::visibleStatuses()])
                    ->orderBy(['post_time' => SORT_DESC])
                    ->all();
                $grouped = [];
                foreach ($posts as $post) {
                    $month = date('Y-m', (int)$post->post_time);
                    $grouped[$month][] = $post;
                }
                return $grouped;
            },
            3600,
        );
        $total = array_sum(array_map(static fn (array $list): int => count($list), $grouped));

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'grouped' => $grouped,
                'total' => $total,
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
