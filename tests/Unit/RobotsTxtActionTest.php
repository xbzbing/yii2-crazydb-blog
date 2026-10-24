<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Option\Option;
use App\Tests\TestCase;
use App\Web\RobotsTxt\Action;
use HttpSoft\Message\ResponseFactory;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * 动态 robots.txt：Disallow 规则 + Sitemap 绝对地址（seo_site_url 配置优先）。
 */
final class RobotsTxtActionTest extends TestCase
{
    /** @var list<array{value: ?string, existed: bool}> */
    private array $snapshots = [];

    private function action(): Action
    {
        return new Action(
            new ResponseFactory(),
            $this->container()->get(UrlGeneratorInterface::class),
            new Cache(new ArrayCache()),
        );
    }

    /** 覆盖 seo_site_url，tearDown 恢复（共享测试库须幂等还原）。 */
    private function seedSiteUrl(string $value): void
    {
        $row = Option::query()->where(['type' => Option::SEO_TYPE, 'name' => Option::SEO_SITE_URL])->one();
        $this->snapshots[] = [
            'value' => $row instanceof Option ? $row->value : null,
            'existed' => $row instanceof Option,
        ];
        if ($row instanceof Option) {
            $row->value = $value;
            $row->update_time = time();
            $row->save();
            return;
        }
        $new = new Option();
        $new->type = Option::SEO_TYPE;
        $new->name = Option::SEO_SITE_URL;
        $new->value = $value;
        $new->save();
    }

    protected function tearDown(): void
    {
        foreach ($this->snapshots as $snap) {
            $row = Option::query()->where(['type' => Option::SEO_TYPE, 'name' => Option::SEO_SITE_URL])->one();
            if ($snap['existed']) {
                if ($row instanceof Option) {
                    $row->value = $snap['value'];
                    $row->update_time = time();
                    $row->save();
                }
            } elseif ($row instanceof Option) {
                (new Option())->deleteAll(['type' => Option::SEO_TYPE, 'name' => Option::SEO_SITE_URL]);
            }
        }
        parent::tearDown();
    }

    public function testDisallowsPrivateRoutesAndAlwaysDeclaresSitemap(): void
    {
        $response = $this->action()->__invoke();
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/plain', $response->getHeaderLine('Content-Type'));

        $body = (string)$response->getBody();
        self::assertStringContainsString("User-agent: *\n", $body);
        self::assertStringContainsString("Disallow: /admin\n", $body);
        self::assertStringContainsString("Disallow: /login\n", $body);
        self::assertStringContainsString("Disallow: /register\n", $body);
        // 测试环境无 last-matched request 且未配置 seo_site_url 时 origin 回退相对路径
        self::assertMatchesRegularExpression('/^Sitemap: \S*sitemap\.xml$/m', $body);
    }

    public function testSitemapUsesConfiguredSiteUrl(): void
    {
        $this->seedSiteUrl('https://seo.test/');

        $body = (string)$this->action()->__invoke()->getBody();
        self::assertStringContainsString("Sitemap: https://seo.test/sitemap.xml\n", $body);
    }
}
