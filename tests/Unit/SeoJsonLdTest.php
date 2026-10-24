<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Web\Shared\SeoJsonLd;
use PHPUnit\Framework\TestCase;

/**
 * SeoJsonLd：WebSite/BlogPosting/BreadcrumbList 节点结构 + 渲染安全（</script> 注入防护）。
 */
final class SeoJsonLdTest extends TestCase
{
    public function testWebSiteNode(): void
    {
        $node = SeoJsonLd::webSite('测试站', 'https://seo.test/', '描述');
        self::assertSame('https://schema.org', $node['@context']);
        self::assertSame('WebSite', $node['@type']);
        self::assertSame('测试站', $node['name']);
        self::assertSame('https://seo.test/', $node['url']);
        self::assertSame('描述', $node['description']);

        // 描述为空时省略
        $node = SeoJsonLd::webSite('站', 'https://seo.test/');
        self::assertArrayNotHasKey('description', $node);
    }

    public function testBlogPostingNodeHasRequiredFields(): void
    {
        $node = SeoJsonLd::blogPosting([
            'headline' => '文章标题',
            'url' => 'https://seo.test/archive/hello',
            'description' => '摘要',
            'image' => 'https://seo.test/cover.jpg',
            'published' => 1700000000,
            'modified' => 1700001000,
            'authorName' => '作者',
            'siteName' => '站点',
        ]);
        self::assertSame('BlogPosting', $node['@type']);
        self::assertSame('文章标题', $node['headline']);
        self::assertSame('https://seo.test/archive/hello', $node['mainEntityOfPage']['@id']);
        self::assertSame('2023-11-14T22:13:20+00:00', $node['datePublished']);
        self::assertSame('作者', $node['author']['name']);
        self::assertSame('站点', $node['publisher']['name']);
        self::assertSame(['https://seo.test/cover.jpg'], $node['image']);
        self::assertSame('摘要', $node['description']);
    }

    public function testBlogPostingOmitsEmptyDescriptionAndImage(): void
    {
        $node = SeoJsonLd::blogPosting([
            'headline' => 'T',
            'url' => 'https://seo.test/x',
            'description' => '',
            'image' => '',
            'published' => 0,
            'modified' => 0,
            'authorName' => 'A',
            'siteName' => 'S',
        ]);
        self::assertArrayNotHasKey('description', $node);
        self::assertArrayNotHasKey('image', $node);
        // 时间戳为 0 时退到 epoch（不产生负数/非法日期）
        self::assertSame('1970-01-01T00:00:00+00:00', $node['datePublished']);
    }

    public function testBreadcrumbPositionsAndLastItemWithoutUrl(): void
    {
        $node = SeoJsonLd::breadcrumb([
            ['name' => '首页', 'url' => 'https://seo.test/'],
            ['name' => '分类', 'url' => 'https://seo.test/catalog/php'],
            ['name' => '文章标题'],
        ]);
        self::assertSame('BreadcrumbList', $node['@type']);
        $items = $node['itemListElement'];
        self::assertCount(3, $items);
        self::assertSame(1, $items[0]['position']);
        self::assertSame('https://seo.test/', $items[0]['item']);
        self::assertSame(3, $items[2]['position']);
        self::assertArrayNotHasKey('item', $items[2]);
    }

    public function testRenderProducesParseableJsonAndNeutralizesScriptTag(): void
    {
        $html = SeoJsonLd::render([
            SeoJsonLd::webSite('站</script><script>alert(1)</script>', 'https://seo.test/'),
        ]);
        self::assertStringStartsWith('<script type="application/ld+json">', $html);
        self::assertStringEndsWith('</script>', $html);
        // JSON_HEX_TAG 把 < > 转义为 < >，无法闭合 script 标签
        self::assertStringNotContainsString('</script><script>', $html);
        self::assertStringContainsString('</script>', $html);

        $json = substr($html, strlen('<script type="application/ld+json">'), -strlen('</script>'));
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('站</script><script>alert(1)</script>', $decoded['name']);
    }

    public function testRenderEmptyArrayReturnsEmptyString(): void
    {
        self::assertSame('', SeoJsonLd::render([]));
    }

    public function testRenderEscapesUnicodeAndSlashes(): void
    {
        $html = SeoJsonLd::render([SeoJsonLd::webSite('中文站', 'https://seo.test/')]);
        self::assertStringContainsString('中文站', $html, 'UNESCAPED_UNICODE：中文不转义为 \u 序列');
        self::assertStringContainsString('https://seo.test/', $html, 'UNESCAPED_SLASHES：URL 保持可读');
    }
}
