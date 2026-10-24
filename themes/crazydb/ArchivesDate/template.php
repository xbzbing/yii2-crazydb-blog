<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Crazydb 主题按月归档页（breadcrumbs + list-header + 文章列表）。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var App\Post\MarkdownRenderer $markdownRenderer
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, string|null> $seoConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $categorySummary
 * @var list<array{totalCount: int, name: string, create_time: int, url: string}> $sidebarTags
 * @var list<array{id: ?int, nickname: string, website: ?string, pid: ?int, post_url: ?string, content: ?string, create_time: ?int, email: string, avatar: string, title: string}> $sidebarComments
 * @var string $date
 * @var int $year
 * @var int $month
 * @var list<App\Post\Post> $posts
 * @var App\Web\Pager $pager
 */

$this->setParameter('categorySummary', $categorySummary);
$this->setParameter('sidebarTags', $sidebarTags);
$this->setParameter('sidebarComments', $sidebarComments);
$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('文章归档：' . $date . ($pager->currentPage > 1 ? ' - 第' . $pager->currentPage . '页' : '') . ' - ' . $siteName);
$this->setParameter('seo_keywords', (string)($seoConfig['seo_keywords'] ?? ''));
$this->setParameter('seo_description', (string)($seoConfig['seo_description'] ?? ''));

// JSON-LD：BreadcrumbList（首页 » 文章归档 » 日期）
$siteUrl = \App\Web\Shared\SeoUrl::site((string)($seoConfig[\App\Option\Option::SEO_SITE_URL] ?? ''));
$this->setParameter('seo_jsonld', [
    \App\Web\Shared\SeoJsonLd::breadcrumb([
        ['name' => '首页', 'url' => \App\Web\Shared\SeoUrl::route($urlGenerator, 'site/index', [], $siteUrl)],
        ['name' => '文章归档', 'url' => \App\Web\Shared\SeoUrl::route($urlGenerator, 'post/archives', [], $siteUrl)],
        ['name' => $date],
    ]),
]);
?>

<div class="breadcrumbs">
    <i class="fa-solid fa-location-dot"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » <a href="<?= $urlGenerator->generate('post/archives') ?>">文章归档</a>
    » <?= Html::encode($date) ?>
</div>

<header class="tag-info list-header">
    <h1><i class="fa-solid fa-calendar-days"></i>文章归档：<?= Html::encode($date) ?> <small>共 <?= (int)$pager->totalCount ?> 篇</small></h1>
</header>

<?= $this->render('PostList/postList.php', [
    'posts' => $posts,
    'pager' => $pager,
    'markdownRenderer' => $markdownRenderer,
    'urlGenerator' => $urlGenerator,
    'categorySummary' => $categorySummary,
    'emptyText' => '该月份暂无文章。',
    'route' => 'post/archives-date',
    'pageRoute' => 'post/archives-date',
    'routeArgs' => ['year' => sprintf('%04d', $year), 'month' => sprintf('%02d', $month)],
]) ?>
