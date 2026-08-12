<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Crazydb 主题文章归档页（breadcrumbs + list-header + 按月分组列表）。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, string|null> $seoConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $categorySummary
 * @var list<array{totalCount: int, name: string, create_time: int, url: string}> $sidebarTags
 * @var list<array{id: ?int, nickname: string, website: ?string, pid: ?int, post_url: ?string, content: ?string, create_time: ?int, email: string, avatar: string, title: string}> $sidebarComments
 * @var array<string, list<App\Post\Post>> $grouped
 * @var int $total
 */

$this->setParameter('categorySummary', $categorySummary);
$this->setParameter('sidebarTags', $sidebarTags);
$this->setParameter('sidebarComments', $sidebarComments);
$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('文章归档 - ' . $siteName);
$this->setParameter('seo_keywords', (string)($seoConfig['seo_keywords'] ?? ''));
$this->setParameter('seo_description', (string)($seoConfig['seo_description'] ?? ''));
?>

<div class="breadcrumbs">
    <i class="fa-solid fa-angle-right"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 文章归档
</div>

<div class="list-header with-shadow">
    <h1><i class="fa-solid fa-box-archive"></i>文章归档（共 <?= $total ?> 篇）</h1>
</div>

<div class="content with-shadow">
    <?php foreach ($grouped as $month => $monthPosts): ?>
        <section class="archives-month">
            <h2><?= Html::encode($month) ?>（<?= count($monthPosts) ?>）</h2>
            <ul>
                <?php foreach ($monthPosts as $post): ?>
                    <li>
                        <a href="<?= Html::encode((string)$post->getUrl($urlGenerator)) ?>"><?= Html::encode($post->title) ?></a>
                        <span class="muted"><?= date('Y-m-d', (int)$post->post_time) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
    <?php if ($grouped === []): ?>
        <p>暂无文章归档。</p>
    <?php endif; ?>
</div>
