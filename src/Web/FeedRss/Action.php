<?php

declare(strict_types=1);

namespace App\Web\FeedRss;

use App\Common\CMSUtils;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * RSS 2.0 订阅输出（最近 20 篇公开文章）。
 * 等价 Yii2 FeedController::actionRss。
 */
final readonly class Action
{
    private const LIMIT = 20;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
        private MarkdownRenderer $markdownRenderer,
    ) {}

    public function __invoke(): ResponseInterface
    {
        $siteConfig = CMSUtils::getSiteConfig($this->cache);
        $seoConfig = CMSUtils::getSiteConfig($this->cache, 'seo');
        $siteName = (string)($siteConfig['site_name'] ?? 'Blog');
        $description = (string)($seoConfig['seo_description'] ?? '');
        $feedUrl = $this->urlGenerator->generateAbsolute('feed/rss');
        $siteUrl = $this->urlGenerator->generateAbsolute('site/index');

        /** @var list<Post> $posts */
        /** @var list<Post> $posts */
        $posts = $this->cache->getOrSet(
            '__feed_rss.' . (int)Post::query()->max('update_time'),
            static fn (): array => Post::query()
                ->where(['status' => Post::STATUS_PUBLISHED])
                ->orderBy(['post_time' => SORT_DESC])
                ->limit(self::LIMIT)
                ->all(),
            300,
        );

        $items = '';
        foreach ($posts as $post) {
            $postUrl = (string)$post->getUrl($this->urlGenerator, true);
            $content = $post->getContentProcessed($this->markdownRenderer);
            $items .= "        <item>\n"
                . '            <title>' . $this->cdata($post->title) . "</title>\n"
                . '            <link>' . $this->escape($postUrl) . "</link>\n"
                . '            <guid isPermaLink="true">' . $this->escape($postUrl) . "</guid>\n"
                . '            <pubDate>' . date('D, d M Y H:i:s O', (int)$post->post_time) . "</pubDate>\n"
                . '            <description>' . $this->cdata($content) . "</description>\n"
                . '            <content:encoded>' . $this->cdata($content) . "</content:encoded>\n"
                . "        </item>\n";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n"
            . "    <channel>\n"
            . '        <title>' . $this->cdata($siteName) . "</title>\n"
            . '        <link>' . $this->escape($siteUrl) . "</link>\n"
            . '        <description>' . $this->cdata($description) . "</description>\n"
            . '        <language>zh-cn</language>' . "\n"
            . '        <lastBuildDate>' . date('D, d M Y H:i:s O', time()) . "</lastBuildDate>\n"
            . '        <atom:link href="' . $this->escape($feedUrl) . '" rel="self" type="application/rss+xml"/>' . "\n"
            . $items
            . "    </channel>\n"
            . '</rss>';

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($xml);
        return $response
            ->withHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->withHeader('Cache-Control', 'no-cache');
    }

    private function cdata(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?? $text;
        return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $text) . ']]>';
    }

    private function escape(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?? $text;
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
