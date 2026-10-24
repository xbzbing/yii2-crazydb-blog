<?php

declare(strict_types=1);

namespace App\Web\Shared;

/**
 * JSON-LD 结构化数据构建（schema.org）：BlogPosting / BreadcrumbList / WebSite。
 * 页面模板把节点数组 setParameter('seo_jsonld', [...])，layout 统一渲染为
 * <script type="application/ld+json">（JSON_HEX_TAG 防 </script> 注入）。
 */
final class SeoJsonLd
{
    /**
     * WebSite 节点（首页）。
     *
     * @return array<string, mixed>
     */
    public static function webSite(string $name, string $url, string $description = ''): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $name,
            'url' => $url,
        ];
        if ($description !== '') {
            $node['description'] = $description;
        }
        return $node;
    }

    /**
     * BlogPosting 节点（文章详情页）。
     *
     * @param array{
     *     headline: string,
     *     url: string,
     *     description: string,
     *     image: string,
     *     published: int,
     *     modified: int,
     *     authorName: string,
     *     siteName: string,
     * } $data
     *
     * @return array<string, mixed>
     */
    public static function blogPosting(array $data): array
    {
        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $data['headline'],
            'url' => $data['url'],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $data['url'],
            ],
            'datePublished' => date('c', max(0, $data['published'])),
            'dateModified' => date('c', max(0, $data['modified'])),
            'author' => [
                '@type' => 'Person',
                'name' => $data['authorName'],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $data['siteName'],
            ],
        ];
        if ($data['description'] !== '') {
            $node['description'] = $data['description'];
        }
        if ($data['image'] !== '') {
            $node['image'] = [$data['image']];
        }
        return $node;
    }

    /**
     * BreadcrumbList 节点。最后一级（当前页）不带 url。
     *
     * @param list<array{name: string, url?: string}> $items 按从首页到当前页的顺序
     * @return array<string, mixed>
     */
    public static function breadcrumb(array $items): array
    {
        $elements = [];
        foreach ($items as $i => $item) {
            $element = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
            ];
            if (isset($item['url'])) {
                $element['item'] = $item['url'];
            }
            $elements[] = $element;
        }
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * 节点数组 → <script type="application/ld+json"> 标签串（空数组返回空串）。
     *
     * @param list<array<string, mixed>> $nodes
     */
    public static function render(array $nodes): string
    {
        $scripts = [];
        foreach ($nodes as $node) {
            $json = json_encode($node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
            if ($json === false) {
                continue;
            }
            $scripts[] = '<script type="application/ld+json">' . $json . '</script>';
        }
        return implode("\n    ", $scripts);
    }
}
