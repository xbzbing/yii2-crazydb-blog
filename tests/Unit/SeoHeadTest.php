<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use App\Web\Shared\SeoHead;
use HttpSoft\Message\UriFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Route;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * SeoHead：canonical 归一（路径分页/查询串分页/utm 丢弃）+ OG/Twitter + feed 发现链接。
 */
final class SeoHeadTest extends TestCase
{
    private function url(): UrlGeneratorInterface
    {
        return $this->container()->get(UrlGeneratorInterface::class);
    }

    /**
     * 构造已匹配路由的 CurrentRoute（仿路由器匹配后的状态）。
     *
     * @param array<string, string> $arguments
     */
    private function matchedRoute(string $routeName, string $pattern, array $arguments, string $uri): CurrentRoute
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(Route::get($pattern)->name($routeName), $arguments);
        $currentRoute->setUri((new UriFactory())->createUri($uri));
        return $currentRoute;
    }

    public function testCanonicalEmptyWhenNoRouteMatched(): void
    {
        self::assertSame('', SeoHead::canonical($this->url(), new CurrentRoute(), 'https://seo.test'));
    }

    public function testCanonicalFromRouteArguments(): void
    {
        $currentRoute = $this->matchedRoute('post/show', '/archive/{alias}', ['alias' => 'hello-world'], '/archive/hello-world?utm_source=x');
        self::assertSame(
            'https://seo.test/archive/hello-world',
            SeoHead::canonical($this->url(), $currentRoute, 'https://seo.test'),
        );
    }

    public function testCanonicalPathPaginationKept(): void
    {
        $currentRoute = $this->matchedRoute('post/list-page', '/posts/page/{page:\d+}', ['page' => '4'], '/posts/page/4');
        self::assertSame(
            'https://seo.test/posts/page/4',
            SeoHead::canonical($this->url(), $currentRoute, 'https://seo.test'),
        );
    }

    public function testCanonicalNormalizesQueryPaginationToPathForm(): void
    {
        // /?page=2 → /page/2（与分页器输出的链接一致）
        $currentRoute = $this->matchedRoute('site/index', '/', [], '/?page=2&utm_campaign=ad');
        self::assertSame(
            'https://seo.test/page/2',
            SeoHead::canonical($this->url(), $currentRoute, 'https://seo.test'),
        );
    }

    public function testCanonicalKeepsQueryPaginationWhenNoPageRoute(): void
    {
        // 归档日期页无 -page 路由：保留 ?page=N
        $currentRoute = $this->matchedRoute('post/archives-date', '/archives/{year}/{month:\d{1,2}}', ['year' => '2024', 'month' => '12'], '/archives/2024/12?page=2');
        self::assertSame(
            'https://seo.test/archives/2024/12?page=2',
            SeoHead::canonical($this->url(), $currentRoute, 'https://seo.test'),
        );
    }

    public function testSocialMetaOmitsEmptyAndEncodesHtml(): void
    {
        $html = SeoHead::socialMeta('站点"A"', '标题<b>', '', 'https://seo.test/x', 'website', '');
        self::assertStringContainsString('property="og:site_name"', $html);
        self::assertStringContainsString('站点&quot;A&quot;', $html);
        self::assertStringContainsString('标题&lt;b&gt;', $html);
        self::assertStringContainsString('property="og:url" content="https://seo.test/x"', $html);
        self::assertStringContainsString('name="twitter:card" content="summary"', $html);
        // 无图不输出 og:image/twitter:image；description 为空不输出
        self::assertStringNotContainsString('og:image', $html);
        self::assertStringNotContainsString('og:description', $html);
    }

    public function testSocialMetaUsesLargeImageCardWhenImagePresent(): void
    {
        $html = SeoHead::socialMeta('站', '题', '述', 'https://seo.test/x', 'article', 'https://seo.test/cover.jpg');
        self::assertStringContainsString('property="og:type" content="article"', $html);
        self::assertStringContainsString('property="og:image" content="https://seo.test/cover.jpg"', $html);
        self::assertStringContainsString('name="twitter:card" content="summary_large_image"', $html);
        self::assertStringContainsString('name="twitter:image" content="https://seo.test/cover.jpg"', $html);
    }

    public function testFeedLinksDiscoverRssAndAtom(): void
    {
        $html = SeoHead::feedLinks($this->url(), 'MySite');
        self::assertStringContainsString('rel="alternate" type="application/rss+xml"', $html);
        self::assertStringContainsString('href="/feed/rss"', $html);
        self::assertStringContainsString('rel="alternate" type="application/atom+xml"', $html);
        self::assertStringContainsString('href="/feed/atom"', $html);
        self::assertStringContainsString('title="MySite RSS"', $html);
    }
}
