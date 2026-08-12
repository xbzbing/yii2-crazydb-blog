<?php

declare(strict_types=1);

namespace App\Web\TagList;

use App\Category\Category;
use App\Comment\Comment;
use App\Common\CMSUtils;
use App\Nav\Nav;
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
    ) {}

    public function __invoke(): ResponseInterface
    {
        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        $tags = Tag::getTags($this->cache, $this->urlGenerator);

        return $this->viewRenderer->render(
            __DIR__ . '/template',
            [
                'tags' => $tags,
                'urlGenerator' => $this->urlGenerator,
                'siteConfig' => $siteConfig,
                'seoConfig' => CMSUtils::getSiteConfig($this->cache, 'seo'),
                'navTree' => Nav::getNavTree($this->cache, $this->urlGenerator),
                'showSidebar' => true,
                'categorySummary' => Category::getCategorySummary($this->cache, $this->urlGenerator),
                // 全量列表按 totalCount 降序，前 20 恰为侧边栏所需，避免二次查询
                'sidebarTags' => array_slice($tags, 0, 20),
                'sidebarComments' => Comment::getRecentComments($this->cache, $this->urlGenerator, $this->aliases, 5),
            ],
        );
    }
}
