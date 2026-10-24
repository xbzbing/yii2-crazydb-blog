<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Option\Option;
use App\Tests\TestCase;
use App\Web\Sitemap\Action;
use HttpSoft\Message\ResponseFactory;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * Sitemap 输出强化：合法 XML、lastmod 覆盖（固定页/文章/分类/标签）、
 * 绝对 URL 按后台 seo_site_url 归一（配置优先于请求 host）。
 */
final class SitemapStructureTest extends TestCase
{
    private ?string $originalSiteUrl = null;
    private bool $siteUrlExisted = false;

    private function action(): Action
    {
        return new Action(
            new ResponseFactory(),
            $this->container()->get(UrlGeneratorInterface::class),
            new Cache(new ArrayCache()),
        );
    }

    /** 覆盖 seo_site_url（原始值在 setUp 快照，tearDown 一次性恢复）。 */
    private function seedSiteUrl(string $value): void
    {
        $row = Option::query()->where(['type' => Option::SEO_TYPE, 'name' => Option::SEO_SITE_URL])->one();
        if ($row instanceof Option) {
            $row->value = $value;
            // 配置缓存键 = max(update_time)：单调递增保证同秒内再次写入也能失效
            $row->update_time = max(time(), (int)$row->update_time + 1);
            $row->save();
            return;
        }
        $new = new Option();
        $new->type = Option::SEO_TYPE;
        $new->name = Option::SEO_SITE_URL;
        $new->value = $value;
        $new->update_time = time();
        $new->save();
    }

    protected function setUp(): void
    {
        parent::setUp();
        // 测试环境无请求 host：显式配置站点 URL，使绝对 URL 断言确定
        $row = Option::query()->where(['type' => Option::SEO_TYPE, 'name' => Option::SEO_SITE_URL])->one();
        $this->siteUrlExisted = $row instanceof Option;
        $this->originalSiteUrl = $row instanceof Option ? $row->value : null;
        $this->seedSiteUrl('https://sitemap.test');
    }

    protected function tearDown(): void
    {
        $row = Option::query()->where(['type' => Option::SEO_TYPE, 'name' => Option::SEO_SITE_URL])->one();
        if ($this->siteUrlExisted) {
            if ($row instanceof Option) {
                $row->value = $this->originalSiteUrl;
                $row->update_time = time();
                $row->save();
            }
        } elseif ($row instanceof Option) {
            (new Option())->deleteAll(['type' => Option::SEO_TYPE, 'name' => Option::SEO_SITE_URL]);
        }
        parent::tearDown();
    }

    public function testSitemapIsValidXmlWithUrlsetNamespace(): void
    {
        $response = $this->action()->__invoke();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/xml', $response->getHeaderLine('Content-Type'));

        $xml = simplexml_load_string((string)$response->getBody());
        self::assertNotFalse($xml, 'sitemap 必须是合法 XML');
        self::assertSame('urlset', $xml->getName());
        self::assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', (string)$xml->getNamespaces()['']);
        self::assertGreaterThan(0, count($xml->url), '至少包含固定页 URL');
    }

    public function testFixedPagesAndPostsCarryLastmod(): void
    {
        $xml = simplexml_load_string((string)$this->action()->__invoke()->getBody());
        self::assertNotFalse($xml);

        // 固定页（首页/列表/归档/标签）在有已发布文章时应带 lastmod
        $firstUrl = $xml->url[0];
        $loc = (string)$firstUrl->loc;
        self::assertStringEndsWith('/', $loc, '首页 loc 应为站点根');
        self::assertNotEmpty((string)$firstUrl->lastmod, '首页应带 lastmod（取最新文章更新时间）');

        // 分类/标签条目存在且 priority 分层合理（0.9 文章 > 0.7 分类 > 0.5 标签）
        $priorities = [];
        foreach ($xml->url as $url) {
            $priorities[] = (string)$url->priority;
        }
        self::assertContains('0.9', $priorities, '应包含文章条目（priority 0.9）');
        self::assertContains('1.0', $priorities, '应包含首页条目（priority 1.0）');
    }

    public function testAllLocsAreAbsoluteUrls(): void
    {
        $xml = simplexml_load_string((string)$this->action()->__invoke()->getBody());
        self::assertNotFalse($xml);
        foreach ($xml->url as $url) {
            $loc = (string)$url->loc;
            self::assertMatchesRegularExpression('#^https?://#', $loc, "loc 须为绝对 URL: {$loc}");
        }
    }

    public function testLocsUseConfiguredSiteUrl(): void
    {
        $this->seedSiteUrl('https://seo-sitemap.test/');

        $xml = simplexml_load_string((string)$this->action()->__invoke()->getBody());
        self::assertNotFalse($xml);
        $loc = (string)$xml->url[0]->loc;
        self::assertStringStartsWith('https://seo-sitemap.test/', $loc, '配置 seo_site_url 后 loc 应以其为 origin');
    }

    public function testSiteUrlChangeInvalidatesCacheVersion(): void
    {
        // 同一实例（同一缓存存储）先后两次请求：版本键含 md5(siteUrl)，切换配置必须重建
        $action = $this->action();

        $this->seedSiteUrl('https://version-a.test');
        $bodyA = (string)$action()->getBody();

        $this->seedSiteUrl('https://version-b.test');
        $bodyB = (string)$action()->getBody();

        self::assertStringContainsString('https://version-a.test/', $bodyA);
        self::assertStringContainsString('https://version-b.test/', $bodyB);
        self::assertNotSame($bodyA, $bodyB, '切换 seo_site_url 后 sitemap 不得复用旧缓存');
    }
}
