<?php

declare(strict_types=1);

namespace App\Web\FeedAtom;

use App\Common\CMSUtils;
use App\Post\MarkdownRenderer;
use App\Post\Post;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Atom 1.0 订阅输出（最近 20 篇公开文章）。
 * 等价 Yii2 FeedController::actionAtom。
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
        $description = (string)($seoConfig['seo_description'] ?? '最新更新的文章');
        $siteUrl = $this->urlGenerator->generateAbsolute('site/index');
        $feedUrl = $this->urlGenerator->generateAbsolute('feed/atom');

        /** @var list<Post> $posts */
        $posts = Post::query()
            ->where(['status' => Post::STATUS_PUBLISHED])
            ->orderBy(['post_time' => SORT_DESC, 'update_time' => SORT_DESC])
            ->limit(self::LIMIT)
            ->all();

        // feed.updated 须 ≥ 所有 entry.updated（Atom 规范），取全量 MAX(update_time)
        $updated = (int)(Post::query()->where(['status' => Post::STATUS_PUBLISHED])->max('update_time') ?? time());

        $entries = '';
        foreach ($posts as $post) {
            $postUrl = (string)$post->getUrl($this->urlGenerator, true);
            $content = $post->getContentProcessed($this->markdownRenderer);
            $entries .= "    <entry>\n"
                . '        <title>' . $this->escape($post->title) . "</title>\n"
                . '        <link href="' . $this->escape($postUrl) . '"/>' . "\n"
                . '        <id>' . $this->escape($postUrl) . "</id>\n"
                . '        <updated>' . date('c', (int)$post->update_time) . "</updated>\n"
                . '        <published>' . date('c', (int)$post->post_time) . "</published>\n"
                . '        <author><name>' . $this->escape($post->author_name !== '' ? $post->author_name : '佚名') . "</name></author>\n"
                . '        <summary>' . $this->escape((string)$post->excerpt) . "</summary>\n"
                . '        <content type="html">' . $this->escape($content) . "</content>\n"
                . "    </entry>\n";
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<feed xmlns="http://www.w3.org/2005/Atom">' . "\n"
            . '    <title>' . $this->escape($siteName) . "</title>\n"
            . '    <subtitle>' . $this->escape($description) . "</subtitle>\n"
            . '    <link href="' . $this->escape($siteUrl) . '"/>' . "\n"
            . '    <link rel="self" href="' . $this->escape($feedUrl) . '"/>' . "\n"
            . '    <id>' . $this->escape($feedUrl) . "</id>\n"
            . '    <updated>' . date('c', $updated) . "</updated>\n"
            . $entries
            . '</feed>';

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($xml);
        return $response
            ->withHeader('Content-Type', 'application/atom+xml; charset=UTF-8')
            ->withHeader('Cache-Control', 'no-cache');
    }

    private function escape(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?? $text;
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
