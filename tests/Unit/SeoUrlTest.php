<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use App\Web\Shared\SeoUrl;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * SeoUrl：seo_site_url 配置优先，回退请求 host（测试环境无请求时回退相对路径）。
 */
final class SeoUrlTest extends TestCase
{
    private function url(): UrlGeneratorInterface
    {
        return $this->container()->get(UrlGeneratorInterface::class);
    }

    public function testSiteNormalizesTrailingSlash(): void
    {
        self::assertSame('https://example.com', SeoUrl::site('https://example.com/'));
        self::assertSame('https://example.com', SeoUrl::site('  https://example.com  '));
    }

    public function testSiteRejectsNonAbsoluteOrNonHttp(): void
    {
        self::assertSame('', SeoUrl::site(''));
        self::assertSame('', SeoUrl::site('example.com'));
        self::assertSame('', SeoUrl::site('ftp://example.com'));
    }

    public function testAbsoluteUsesConfiguredSiteUrl(): void
    {
        self::assertSame(
            'https://seo.test/archive/hello',
            SeoUrl::absolute($this->url(), '/archive/hello', 'https://seo.test/'),
        );
    }

    public function testAbsoluteWithoutConfigFallsBackToRequestOriginOrRelative(): void
    {
        // 测试环境无 last-matched request：generateAbsolute 返回相对路径 → origin 解析不出 → 返回相对路径
        $result = SeoUrl::absolute($this->url(), '/archive/hello', '');
        self::assertStringContainsString('/archive/hello', $result);
    }

    public function testRouteBuildsAbsoluteUrlFromConfiguredSite(): void
    {
        self::assertSame(
            'https://seo.test/sitemap.xml',
            SeoUrl::route($this->url(), 'sitemap', [], 'https://seo.test'),
        );
    }
}
