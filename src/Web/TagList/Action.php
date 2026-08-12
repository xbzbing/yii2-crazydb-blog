<?php

declare(strict_types=1);

namespace App\Web\TagList;

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
 * 标签列表（等价 Yii2 TagController::actionList）：全部标签聚合展示。
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
        $tags = Tag::getTags($this->cache, $this->urlGenerator);

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'tags' => $tags,
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
