<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 标签列表页（等价 Yii2 views/tag/list.php）。
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
 * @var list<array{totalCount: int, name: string, create_time: int, url: string}> $tags
 */

$this->setParameter('categorySummary', $categorySummary);
$this->setParameter('sidebarTags', $sidebarTags);
$this->setParameter('sidebarComments', $sidebarComments);
$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('所有标签 - ' . $siteName);
$this->setParameter('seo_keywords', (string)($seoConfig['seo_keywords'] ?? ''));
$this->setParameter('seo_description', (string)($seoConfig['seo_description'] ?? ''));
?>

<nav class="breadcrumb">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 所有标签
</nav>

<article class="article-card">
    <header class="article-card-header">
        <h1>所有标签</h1>
    </header>
    <div class="article-card-content">
        <div class="tag-cloud">
            <?php if ($tags === []): ?>
                <p>暂无标签。</p>
            <?php endif; ?>
            <?php foreach ($tags as $tag): ?>
                <a href="<?= Html::encode($tag['url']) ?>" title="<?= Html::encode($tag['name']) ?>（<?= (int)$tag['totalCount'] ?> 篇文章）">
                    <?= Html::encode($tag['name']) ?>
                    <span class="tag-count"><?= (int)$tag['totalCount'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</article>
