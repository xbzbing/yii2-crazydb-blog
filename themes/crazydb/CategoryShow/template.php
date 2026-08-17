<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Crazydb 主题分类页（breadcrumbs + list-header + 文章列表）。
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
 * @var App\Category\Category $category
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
$this->setTitle($category->name . ' - ' . $siteName);
$this->setParameter('seo_keywords', (string)($category->keywords !== '' ? $category->keywords : ($seoConfig['seo_keywords'] ?? '')));
$this->setParameter('seo_description', (string)($category->desc ?? ''));
?>

<div class="breadcrumbs">
    <i class="fa-solid fa-location-dot"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » <a href="<?= $urlGenerator->generate('post/archives') ?>">文章归档</a>
    » 分类文章
</div>

<header class="tag-info list-header">
    <h1><i class="fa-solid fa-list"></i><?= Html::encode($category->name) ?> <small>共 <?= (int)$pager->totalCount ?> 篇</small></h1>
</header>

<?= $this->render('PostList/postList.php', [
    'posts' => $posts,
    'pager' => $pager,
    'markdownRenderer' => $markdownRenderer,
    'urlGenerator' => $urlGenerator,
    'categorySummary' => $categorySummary,
    'emptyText' => '该分类下暂无文章。',
    'route' => 'category/show',
    'pageRoute' => 'category/show-page',
    'routeArgs' => ['alias' => $category->alias],
]) ?>
