<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Crazydb 主题文章归档页（忠实还原线上：蓝色圆点时间轴 + 年/月节点）。
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

<div class="breadcrumbs with-shadow">
    <i class="fa-solid fa-location-dot"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 文章归档
</div>

<div id="archives">
    <h2>文章归档<small>共 <?= $total ?> 篇</small></h2>
    <ul class="archives">
        <li class="future" title="奋然前行"><em class="fa-solid fa-chevron-up"></em></li>
        <?php foreach ($grouped as $month => $monthPosts): ?>
            <?php [$year, $monthNum] = array_map('intval', explode('-', $month) + ['', '']); ?>
            <?php $yearKey = (string)$year; ?>
            <?php if ($yearKey !== '' && ($displayedYear ?? null) !== $yearKey): ?>
                <?php $displayedYear = $yearKey; ?>
                <?php $yearCount = 0; foreach ($grouped as $m => $ps) { if (str_starts_with($m, $yearKey)) $yearCount += count($ps); } ?>
                <li class="year"><h3 class="year"><?= $yearKey ?> 年（<?= $yearCount ?> 篇）</h3></li>
            <?php endif; ?>
            <li class="month"><h4><?= $monthNum ?>月（<?= count($monthPosts) ?> 篇）</h4></li>
            <?php $index = 1; ?>
            <?php foreach ($monthPosts as $post): ?>
                <?php $category = $categorySummary[(int)$post->cid] ?? null; ?>
                <li class="<?= $index > 1 ? 'else' : 'first' ?>">
                    <em><?= date('d', (int)$post->post_time) ?></em>
                    <a href="<?= Html::encode((string)$post->getUrl($urlGenerator)) ?>" title="<?= Html::encode($post->title) ?>" target="_blank"><?= Html::encode($post->title) ?></a>
                    <?php if ($category !== null && $category['url'] !== null): ?>
                        <small>[<a href="<?= Html::encode((string)$category['url']) ?>" title="<?= Html::encode((string)$category['name']) ?>" target="_blank"><?= Html::encode((string)$category['name']) ?></a>]</small>
                    <?php endif; ?>
                    <time><?= date('Y/m/d', (int)$post->post_time) ?></time>
                </li>
                <?php $index++; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if ($grouped === []): ?>
            <li>&nbsp;&nbsp;暂未发布公开文章</li>
        <?php endif; ?>
        <li class="previous" title="不忘初心"><em class="fa-solid fa-chevron-down"></em></li>
    </ul>
</div>
