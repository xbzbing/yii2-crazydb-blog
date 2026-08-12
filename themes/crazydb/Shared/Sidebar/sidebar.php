<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Crazydb 主题侧栏 widgets（还原线上：关于我/分类文章/标签/最新评论）。
 *
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $categorySummary
 * @var list<array{totalCount: int, name: string, create_time: int, url: string}> $tags
 * @var list<array{id: ?int, nickname: string, website: ?string, pid: ?int, post_url: ?string, content: ?string, create_time: ?int, email: string, avatar: string, title: string}> $recentComments
 */

$siteName = (string)($siteConfig['site_name'] ?? '');
$adminEmail = (string)($siteConfig['admin_email'] ?? '');
$copyright = (string)($siteConfig['copyright'] ?? '');
$siteIcp = (string)($siteConfig['site_icp'] ?? '');
?>

<div class="widget aside-about with-shadow" id="about-me">
    <h3 class="widget_title"><i class="fa-solid fa-user"></i>关于我</h3>
    <ul>
        <li>
            <p>曾经是爱好网络安全的程序猿</p>
            <p>后来是爱好编程的安全攻城狮</p>
            <p>现在是爱好安全的摸鱼工程师</p>
            <address><?= Html::encode($adminEmail !== '' ? $adminEmail : 'admin#crazydb.com') ?></address>
        </li>
    </ul>
</div>

<div class="widget aside-categories with-shadow">
    <h3 class="widget_title">
        <i class="fa-solid fa-list"></i>分类文章
        <a href="<?= $urlGenerator->generate('feed/rss') ?>" target="_blank" title="RSS订阅"><em class="rss-feed"></em></a>
    </h3>
    <ul>
        <?php foreach ($categorySummary as $category): ?>
            <?php if ($category['url'] !== null && $category['postCount'] > 0): ?>
                <li>
                    <a href="<?= Html::encode((string)$category['url']) ?>" title="<?= Html::encode((string)$category['name']) ?>"><?= Html::encode((string)$category['name']) ?></a>
                    （<?= (int)$category['postCount'] ?> 篇）
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li>
            <i class="fa-solid fa-hand-point-right"></i>&nbsp;&nbsp;
            <a href="<?= $urlGenerator->generate('post/archives') ?>" target="_blank">文章归档</a>
        </li>
    </ul>
</div>

<div class="widget aside-tags with-shadow">
    <h3 class="widget_tit"><i class="fa-solid fa-tags"></i>标签</h3>
    <div>
        <?php foreach ($tags as $tag): ?>
            <a href="<?= Html::encode((string)$tag['url']) ?>" title="<?= Html::encode((string)$tag['name']) ?>(<?= (int)$tag['totalCount'] ?>)" target="_blank">
                <span class="badge text-bg-primary"><?= Html::encode((string)$tag['name']) ?></span>
            </a>
        <?php endforeach; ?>
        <div class="more-link">
            <i class="fa-solid fa-hand-point-right"></i>&nbsp;&nbsp;
            <a href="<?= $urlGenerator->generate('tag/list') ?>" target="_blank" title="所有标签">所有标签</a>
        </div>
    </div>
</div>

<div class="widget aside-comments with-shadow">
    <h3 class="widget_title"><i class="fa-solid fa-comment"></i>最新评论</h3>
    <ul>
        <?php if ($recentComments === []): ?>
            <li>暂无评论</li>
        <?php endif; ?>
        <?php foreach ($recentComments as $comment): ?>
            <li>
                <img alt="<?= Html::encode((string)$comment['nickname']) ?>" title="<?= Html::encode((string)$comment['nickname']) ?>" src="<?= Html::encode((string)$comment['avatar']) ?>" class="avatar img-thumbnail comment-avatar" height="40" width="40">
                <a href="<?= Html::encode((string)($comment['post_url'] ?? '#')) ?>#comment-<?= (int)($comment['id'] ?? 0) ?>" title="查看详情">
                    <strong><?= Html::encode((string)$comment['nickname']) ?>：</strong>
                </a>
                <span title="发表在：<?= Html::encode((string)$comment['title']) ?>"><?= Html::encode((string)$comment['content']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
