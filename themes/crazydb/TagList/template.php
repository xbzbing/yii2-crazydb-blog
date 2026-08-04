<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Crazydb 主题标签列表页（list-header + 标签云，还原线上 aside-tags 视觉）。
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
$tagNames = array_slice(array_map(static fn (array $tag): string => (string)$tag['name'], $tags), 0, 20);
$keywords = (string)($seoConfig['seo_keywords'] ?? '');
if ($tagNames !== []) {
    $keywords = implode(',', $tagNames) . ($keywords !== '' ? ',' . $keywords : '');
}
$this->setParameter('seo_keywords', $keywords);
$this->setParameter('seo_description', (string)($seoConfig['seo_description'] ?? ''));

$tagColors = ['default', 'primary', 'success', 'info', 'warning', 'danger'];
?>

<div class="breadcrumbs with-shadow">
    <i class="fa-solid fa-location-dot"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 所有标签
</div>

<div class="list-header with-shadow">
    <h1><i class="fa-solid fa-tags"></i>所有标签</h1>
</div>

<div class="aside-tags with-shadow">
    <div class="content">
        <?php if ($tags === []): ?>
            <p>暂无标签。</p>
        <?php endif; ?>
        <?php foreach ($tags as $tag): ?>
            <?php $color = $tagColors[random_int(0, 5)]; ?>
            <a href="<?= Html::encode($tag['url']) ?>" title="<?= Html::encode($tag['name']) ?>（<?= (int)$tag['totalCount'] ?> 篇文章）" target="_blank">
                <span class="label label-<?= $color ?>"><?= Html::encode($tag['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
