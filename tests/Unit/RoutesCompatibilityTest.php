<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use HttpSoft\Message\ServerRequestFactory;
use Yiisoft\Router\UrlMatcherInterface;

/**
 * URL compatibility: every legacy route pattern (docs/url-compatibility.md)
 * must match in the Yii3 router with the expected route name.
 */
final class RoutesCompatibilityTest extends TestCase
{
    /**
     * @param array<string, string> $cases URL => expected route name
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('urlCases')]
    public function testRouteMatches(string $url, string $expectedRoute): void
    {
        $matcher = $this->container()->get(UrlMatcherInterface::class);
        $request = (new ServerRequestFactory())->createServerRequest('GET', $url);

        $result = $matcher->match($request);

        self::assertTrue($result->isSuccess(), "URL $url should match a route");
        self::assertSame($expectedRoute, $result->route()->getData('name'), "URL $url should map to $expectedRoute");
    }

    public static function urlCases(): iterable
    {
        yield ['/', 'site/index'];
        yield ['/page/2', 'site/index-page'];
        yield ['/login', 'site/login'];
        yield ['/logout', 'site/logout'];
        yield ['/register', 'site/register'];
        yield ['/site/captcha', 'site/captcha'];
        yield ['/catalog/php', 'category/show'];
        yield ['/catalog/php/page/3', 'category/show-page'];
        yield ['/archive/hello-world', 'post/show'];
        yield ['/posts', 'post/list'];
        yield ['/posts/page/4', 'post/list-page'];
        yield ['/post/123', 'post/view'];
        yield ['/archives', 'post/archives'];
        yield ['/archives/2024/12', 'post/archives-date'];
        yield ['/tag/yii3', 'tag/show'];
        yield ['/tag/yii3/page/2', 'tag/show-page'];
        yield ['/tags', 'tag/list'];
        yield ['/user/dabing', 'user/show'];
        yield ['/user/dabing/page/2', 'user/show-page'];
        yield ['/user/profile/dabing', 'user/profile'];
        yield ['/user/profile', 'user/profile-me'];
        yield ['/user/modify-password', 'user/modify-password'];
        yield ['/comment/add/42', 'comment/add'];
        yield ['/comment/7', 'comment/view'];
        yield ['/comments', 'comment/list'];
        yield ['/feed/rss', 'feed/rss'];
        yield ['/feed/atom', 'feed/atom'];
        yield ['/tool/image-upload', 'tool/image-upload'];
        yield ['/tool/captcha', 'tool/captcha'];
    }
}
