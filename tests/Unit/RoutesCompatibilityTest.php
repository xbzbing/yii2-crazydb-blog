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
     * @param array{url: string, method?: string} $case
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('urlCases')]
    public function testRouteMatches(array $case): void
    {
        $matcher = $this->container()->get(UrlMatcherInterface::class);
        $request = (new ServerRequestFactory())->createServerRequest($case['method'] ?? 'GET', $case['url']);

        $result = $matcher->match($request);

        self::assertTrue($result->isSuccess(), "URL {$case['url']} should match a route");
        self::assertSame($case['route'], $result->route()->getData('name'), "URL {$case['url']} should map to {$case['route']}");
    }

    public static function urlCases(): iterable
    {
        yield [[ 'url' => '/', 'route' => 'site/index' ]];
        yield [[ 'url' => '/page/2', 'route' => 'site/index-page' ]];
        yield [[ 'url' => '/login', 'route' => 'site/login' ]];
        yield [[ 'url' => '/logout', 'method' => 'POST', 'route' => 'site/logout' ]];
        yield [[ 'url' => '/register', 'route' => 'site/register' ]];
        yield [[ 'url' => '/site/captcha', 'route' => 'site/captcha' ]];
        yield [[ 'url' => '/catalog/php', 'route' => 'category/show' ]];
        yield [[ 'url' => '/catalog/php/page/3', 'route' => 'category/show-page' ]];
        yield [[ 'url' => '/archive/hello-world', 'route' => 'post/show' ]];
        yield [[ 'url' => '/posts', 'route' => 'post/list' ]];
        yield [[ 'url' => '/posts/page/4', 'route' => 'post/list-page' ]];
        yield [[ 'url' => '/post/123', 'route' => 'post/view' ]];
        yield [[ 'url' => '/archives', 'route' => 'post/archives' ]];
        yield [[ 'url' => '/archives/2024/12', 'route' => 'post/archives-date' ]];
        yield [[ 'url' => '/tag/yii3', 'route' => 'tag/show' ]];
        yield [[ 'url' => '/tag/yii3/page/2', 'route' => 'tag/show-page' ]];
        yield [[ 'url' => '/tags', 'route' => 'tag/list' ]];
        yield [[ 'url' => '/user/dabing', 'route' => 'user/show' ]];
        yield [[ 'url' => '/user/dabing/page/2', 'route' => 'user/show-page' ]];
        yield [[ 'url' => '/user/profile/dabing', 'route' => 'user/profile' ]];
        yield [[ 'url' => '/user/profile', 'route' => 'user/profile-edit' ]];
        yield [[ 'url' => '/user/modify-password', 'route' => 'user/modify-password' ]];
        yield [[ 'url' => '/comment/add/42', 'method' => 'POST', 'route' => 'comment/add' ]];
        yield [[ 'url' => '/comment/7', 'route' => 'comment/view' ]];
        yield [[ 'url' => '/comments', 'route' => 'comment/list' ]];
        yield [[ 'url' => '/feed/rss', 'route' => 'feed/rss' ]];
        yield [[ 'url' => '/feed/atom', 'route' => 'feed/atom' ]];
        yield [[ 'url' => '/tool/image-upload', 'route' => 'tool/image-upload' ]];
        yield [[ 'url' => '/tool/captcha', 'route' => 'tool/captcha' ]];
    }
}
