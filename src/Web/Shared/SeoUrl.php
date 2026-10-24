<?php

declare(strict_types=1);

namespace App\Web\Shared;

use Yiisoft\Router\UrlGeneratorInterface;

/**
 * SEO 绝对 URL 生成：站点配置了 seo_site_url（如 https://example.com）时以它为基准
 * （反代/内网部署下 request host 不可信），否则回退到当前请求 host。
 */
final class SeoUrl
{
    /**
     * 归一化站点 URL：去空白与尾部斜杠；非 http(s) 绝对地址返回空串（视为未配置）。
     */
    public static function site(string $siteUrl): string
    {
        $siteUrl = trim($siteUrl);
        if ($siteUrl === '' || preg_match('#^https?://#i', $siteUrl) !== 1) {
            return '';
        }
        return rtrim($siteUrl, '/');
    }

    /**
     * 站点根 origin（scheme://host[:port]）：配置优先，否则取 site/index 绝对 URL 的 origin。
     * 两者都拿不到时返回空串（调用方回退相对 URL）。
     */
    public static function origin(UrlGeneratorInterface $urlGenerator, string $siteUrl = ''): string
    {
        $siteUrl = self::site($siteUrl);
        if ($siteUrl !== '') {
            return $siteUrl;
        }
        $home = $urlGenerator->generateAbsolute('site/index');
        $parts = parse_url($home);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return '';
        }
        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }
        return $origin;
    }

    /**
     * 相对路径 → 绝对 URL（origin 拿不到时返回相对路径）。
     */
    public static function absolute(UrlGeneratorInterface $urlGenerator, string $path, string $siteUrl = ''): string
    {
        $origin = self::origin($urlGenerator, $siteUrl);
        if ($origin === '') {
            return $path;
        }
        return $origin . '/' . ltrim($path, '/');
    }

    /**
     * 路由 → 绝对 URL。
     *
     * @param array<string, scalar|null> $params
     */
    public static function route(UrlGeneratorInterface $urlGenerator, string $name, array $params = [], string $siteUrl = ''): string
    {
        return self::absolute($urlGenerator, $urlGenerator->generate($name, $params), $siteUrl);
    }
}
