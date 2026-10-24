<?php

declare(strict_types=1);

namespace App\Web\RobotsTxt;

use App\Common\CMSUtils;
use App\Option\Option;
use App\Web\Shared\SeoUrl;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * 动态 robots.txt：Disallow 规则 + Sitemap 绝对地址。
 * Sitemap 的 origin 优先用后台 seo_site_url（反代部署下 request host 不可信）。
 */
final readonly class Action
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $urlGenerator,
        private CacheInterface $cache,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        $seoConfig = CMSUtils::getSiteConfig($this->cache, Option::SEO_TYPE);
        $siteUrl = (string)($seoConfig[Option::SEO_SITE_URL] ?? '');
        $sitemap = SeoUrl::route($this->urlGenerator, 'sitemap', [], $siteUrl);

        $body = "User-agent: *\n"
            . "Allow: /\n"
            . "Disallow: /admin\n"
            . "Disallow: /login\n"
            . "Disallow: /register\n"
            . "\n"
            . "Sitemap: {$sitemap}\n";

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($body);
        return $response
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }
}
