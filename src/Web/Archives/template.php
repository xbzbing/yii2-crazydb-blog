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

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('文章归档 - ' . $siteName);
$this->setParameter('seo_keywords', (string)($siteConfig['seo_keywords'] ?? ''));
$this->setParameter('seo_description', (string)($siteConfig['seo_description'] ?? ''));
?>

<nav class="breadcrumb">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 文章归档
</nav>

<article class="article-card">
    <header class="article-card-header">
        <h1>文章归档（共 <?= $total ?> 篇）</h1>
    </header>
    <div class="article-card-content">
        <?php foreach ($grouped as $month => $monthPosts): ?>
            <section class="archives-month">
                <h2><?= Html::encode($month) ?>（<?= count($monthPosts) ?>）</h2>
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
