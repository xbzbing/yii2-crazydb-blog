<?php

declare(strict_types=1);

namespace App\Web\Shared;

use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Html\Html;

/**
 * layout <head> 中 SEO 标签的统一生成：
 * - canonical（含分页查询参数归一）
 * - Open Graph / Twitter Card
 * 三套主题 layout 共用，保证输出一致。
 */
final class SeoHead
{
    /**
     * 当前页 canonical 绝对 URL：路由参数生成路径（含 /page/2 路径分页），
     * 查询串里的 ?page=N（legacy 回退）归一到 -page 路径形式（无该路由则保留查询串），
     * 其余查询参数（utm 等）丢弃。无法判定（未匹配路由）返回空串。
     */
    public static function canonical(UrlGeneratorInterface $urlGenerator, ?CurrentRoute $currentRoute, string $siteUrl = ''): string
    {
        if ($currentRoute === null) {
            return '';
        }
        $routeName = $currentRoute->getName();
        $uri = $currentRoute->getUri();
        if ($routeName === null || $uri === null) {
            return '';
        }
        $args = $currentRoute->getArguments();
        if (isset($args['page'])) {
            // 路径分页（/page/2、/posts/page/4）：直接按路由参数生成
            return SeoUrl::absolute($urlGenerator, $urlGenerator->generate($routeName, $args), $siteUrl);
        }
        $params = [];
        parse_str($uri->getQuery(), $params);
        $page = (int)($params['page'] ?? 1);
        if ($page <= 1) {
            return SeoUrl::absolute($urlGenerator, $urlGenerator->generate($routeName, $args), $siteUrl);
        }
        // 查询串分页（/?page=2）优先归一为 -page 路径形式（与分页器输出的链接一致）
        try {
            return SeoUrl::absolute(
                $urlGenerator,
                $urlGenerator->generate($routeName . '-page', $args + ['page' => $page]),
                $siteUrl,
            );
        } catch (\Throwable) {
            // 无 -page 路由（如归档日期页的 ?page=2）：保留查询串形式
            return SeoUrl::absolute(
                $urlGenerator,
                $urlGenerator->generate($routeName, $args) . '?page=' . $page,
                $siteUrl,
            );
        }
    }

    /**
     * Open Graph + Twitter Card meta 标签（内容为空的标签自动省略）。
     *
     * @param string $type og:type（文章详情页传 article，其余默认 website）
     * @param string $image og:image 绝对地址（无封面传空）
     */
    public static function socialMeta(
        string $siteName,
        string $title,
        string $description,
        string $canonical,
        string $type = 'website',
        string $image = '',
    ): string {
        $tags = [];
        $add = static function (string $name, string $content) use (&$tags): void {
            if ($content !== '') {
                $tags[] = sprintf('<meta property="%s" content="%s">', Html::encodeAttribute($name), Html::encodeAttribute($content));
            }
        };
        $add('og:site_name', $siteName);
        $add('og:title', $title);
        $add('og:description', $description);
        $add('og:type', $type);
        $add('og:url', $canonical);
        $add('og:image', $image);

        // twitter 无 og:image 时用 summary 卡片，有则用大图卡片
        $twitter = [];
        $addTwitter = static function (string $name, string $content) use (&$twitter): void {
            if ($content !== '') {
                $twitter[] = sprintf('<meta name="%s" content="%s">', Html::encodeAttribute($name), Html::encodeAttribute($content));
            }
        };
        $addTwitter('twitter:card', $image !== '' ? 'summary_large_image' : 'summary');
        $addTwitter('twitter:title', $title);
        $addTwitter('twitter:description', $description);
        $addTwitter('twitter:image', $image);

        return implode("\n    ", array_merge($tags, $twitter));
    }

    /**
     * RSS/Atom 发现链接（feed discovery）。
     */
    public static function feedLinks(UrlGeneratorInterface $urlGenerator, string $siteName): string
    {
        return sprintf(
            '<link rel="alternate" type="application/rss+xml" title="%s RSS" href="%s">' . "\n    "
            . '<link rel="alternate" type="application/atom+xml" title="%s Atom" href="%s">',
            Html::encodeAttribute($siteName),
            Html::encodeAttribute($urlGenerator->generate('feed/rss')),
            Html::encodeAttribute($siteName),
            Html::encodeAttribute($urlGenerator->generate('feed/atom')),
        );
    }
}
