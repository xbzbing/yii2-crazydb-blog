<?php

declare(strict_types=1);

namespace App\Web\Sitemap;

use App\Category\Category;
use App\Common\CMSUtils;
use App\Option\Option;
use App\Post\Post;
use App\Tag\Tag;
use App\Web\Shared\SeoUrl;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * XML Sitemap：固定页 + 文章（published）+ 分类 + 标签（版本化缓存 1h，单文件最多 50,000 URL）。
 * 绝对 URL 优先用后台 seo_site_url（反代部署下 request host 不可信）。
 * lastmod：文章/分类用 update_time，标签用 create_time（标签表无 update_time）。
 */
final readonly class Action
{
    /** Sitemap XML 协议规定的单文件最大 URL 数。 */
    private const MAX_URLS = 50000;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        $siteUrl = $this->siteUrl();
        // 版本键纳入站点 URL：切换 seo_site_url 后 origin 变化须失效重建
        $version = max(
            (int)Post::query()->max('update_time'),
            (int)Category::query()->max('update_time'),
            (int)Tag::query()->max('id'),
        ) . '.' . md5($siteUrl);
        /** @var string $body */
        $body = $this->cache->getOrSet(
            '__sitemap.' . $version,
            fn (): string => $this->buildXml($siteUrl),
            3600,
        );

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($body);
        return $response
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }

    private function siteUrl(): string
    {
        $seoConfig = CMSUtils::getSiteConfig($this->cache, Option::SEO_TYPE);
        return SeoUrl::site((string)($seoConfig[Option::SEO_SITE_URL] ?? ''));
    }

    private function buildXml(string $siteUrl): string
    {
        $fixedPages = [
            ['route' => 'site/index', 'priority' => '1.0'],
            ['route' => 'post/list', 'priority' => '0.8'],
            ['route' => 'post/archives', 'priority' => '0.6'],
            ['route' => 'tag/list', 'priority' => '0.6'],
        ];
        // 固定页 lastmod：取全站最新文章更新时间（列表/归档内容随文章变化）
        $latestPostUpdate = (int)Post::query()->where(['status' => Post::STATUS_PUBLISHED])->max('update_time');
        $lastmod = $latestPostUpdate > 0 ? date('c', $latestPostUpdate) : null;
        $urls = [];
        foreach ($fixedPages as $page) {
            $urls[] = [
                'loc' => SeoUrl::route($this->urlGenerator, $page['route'], [], $siteUrl),
                'lastmod' => $lastmod,
                'priority' => $page['priority'],
            ];
        }

        /** @var list<Post> $posts */
        $posts = Post::query()
            ->select('id,alias,title,update_time')
            ->where(['status' => Post::STATUS_PUBLISHED])
            ->orderBy(['update_time' => SORT_DESC])
            ->limit(self::MAX_URLS - count($urls))
            ->all();
        foreach ($posts as $post) {
            if (count($urls) >= self::MAX_URLS) {
                break;
            }
            $path = $post->getUrl($this->urlGenerator);
            if ($path !== null) {
                $urls[] = [
                    'loc' => SeoUrl::absolutize($this->urlGenerator, $path, $siteUrl),
                    'lastmod' => date('c', (int)$post->update_time),
                    'priority' => '0.9',
                ];
            }
        }

        /** @var list<Category> $categories */
        $categories = Category::query()->limit(self::MAX_URLS - count($urls))->all();
        foreach ($categories as $category) {
            if (count($urls) >= self::MAX_URLS) {
                break;
            }
            // Category::getUrl 恒返回绝对 URL（忽略 schema 参数），这里自建相对路径再按站点 URL 归一
            $categoryPath = $category->alias !== ''
                ? $this->urlGenerator->generate('category/show', ['alias' => $category->alias])
                : $this->urlGenerator->generate('category/view', ['id' => $category->id]);
            $urls[] = [
                'loc' => SeoUrl::absolutize($this->urlGenerator, $categoryPath, $siteUrl),
                'lastmod' => (int)$category->update_time > 0 ? date('c', (int)$category->update_time) : null,
                'priority' => '0.7',
            ];
        }

        /** @var list<array{name: string, create_time: int|string|null}> $tagRows */
        $tagRows = Tag::query()
            ->select(['create_time' => 'MAX(create_time)', 'name'])
            ->groupBy('name')
            ->limit(self::MAX_URLS - count($urls))
            ->asArray()
            ->all();
        foreach ($tagRows as $row) {
            if (count($urls) >= self::MAX_URLS) {
                break;
            }
            $createTime = (int)($row['create_time'] ?? 0);
            $urls[] = [
                'loc' => SeoUrl::route($this->urlGenerator, 'tag/show', ['name' => (string)$row['name']], $siteUrl),
                'lastmod' => $createTime > 0 ? date('c', $createTime) : null,
                'priority' => '0.5',
            ];
        }

        $body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $body .= "  <url>\n"
                . '    <loc>' . $this->escape($url['loc']) . "</loc>\n"
                . ($url['lastmod'] !== null ? '    <lastmod>' . $this->escape($url['lastmod']) . "</lastmod>\n" : '')
                . '    <priority>' . $url['priority'] . "</priority>\n"
                . "  </url>\n";
        }
        $body .= '</urlset>';

        return $body;
    }

    private function escape(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?? $text;
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
