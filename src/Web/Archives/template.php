<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 文章归档页（等价 Yii2 views/post/archives.php）。
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

<nav class="breadcrumb">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 文章归档
</nav>

<article class="article-card">
    <header class="article-card-header">
        <h2>文章归档（共 <?= $total ?> 篇）</h2>
    </header>
    <div class="article-card-content">
        <?php $displayedYear = null; foreach ($grouped as $month => $monthPosts): ?>
            <?php [$year, $monthNum] = array_map('intval', explode('-', $month) + ['', '']); ?>
            <?php $yearKey = (string)$year; ?>
            <?php if ($displayedYear !== $yearKey): ?>
                <?php $displayedYear = $yearKey; ?>
                <?php $yearCount = 0; foreach ($grouped as $m => $ps) { if (str_starts_with($m, $yearKey)) $yearCount += count($ps); } ?>
                <h3 class="archives-year"><?= $yearKey ?> 年（<?= $yearCount ?> 篇）</h3>
            <?php endif; ?>
            <section class="archives-month">
                <h4><?= $monthNum ?>月（<?= count($monthPosts) ?> 篇）</h4>
                <ul>
                    <?php foreach ($monthPosts as $post): ?>
                        <li>
                            <a href="<?= Html::encode((string)$post->getUrl($urlGenerator)) ?>"><?= Html::encode($post->title) ?></a>
                            <span><?= date('Y-m-d', (int)$post->post_time) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
        <?php if ($grouped === []): ?>
            <p>暂无文章归档。</p>
        <?php endif; ?>
    </div>
</article>
