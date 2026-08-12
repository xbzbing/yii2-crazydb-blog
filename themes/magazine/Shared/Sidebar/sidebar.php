<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 墨刊侧边栏（栏目索引/标签云/关于）。
 *
 * @var array<string, mixed> $siteConfig
 * @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $categorySummary
 * @var list<array{totalCount: int, name: string, create_time: int, url: string}> $tags
 * @var list<array{id: int, nickname: string, website: ?string, pid: int, post_url: ?string, content: ?string, create_time: int, email: string, avatar: string, title: string}> $recentComments
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */
?>

<div class="widget widget-about">
    <h3>关于</h3>
    <?php
    $aboutMe = \App\CustomConfig\CustomConfig::value('ThemeDIY', 'aboutMe');
    ?>
    <?php if ($aboutMe !== null && $aboutMe !== '' && isset($markdownRenderer) && $markdownRenderer !== null): ?>
        <div class="about-me-content"><?= $markdownRenderer->render($aboutMe) ?></div>
    <?php else: ?>
        <p>坚持写博客，记录技术点滴与生活思考。</p>
        <p><?= Html::encode((string)($siteConfig['admin_email'] ?? '')) ?></p>
    <?php endif; ?>
</div>

<div class="widget widget-categories">
    <h3>栏目索引</h3>
    <ul>
        <?php foreach ($categorySummary as $category): ?>
            <?php if ($category['postCount'] < 1) {
                continue;
            } ?>
            <li>
                <a href="<?= Html::encode((string)$category['url']) ?>"><?= Html::encode($category['name']) ?></a>
                （<?= (int)$category['postCount'] ?> 篇）
            </li>
        <?php endforeach; ?>
        <li><a href="<?= $urlGenerator->generate('post/archives') ?>">全部归档</a></li>
    </ul>
</div>

<div class="widget widget-tags">
    <h3>标签云</h3>
    <div class="tag-cloud">
        <?php if ($tags === []): ?>
            <p>暂无标签</p>
        <?php endif; ?>
        <?php foreach ($tags as $tag): ?>
            <a href="<?= Html::encode($tag['url']) ?>" title="<?= Html::encode($tag['name']) ?>（<?= (int)$tag['totalCount'] ?>）"><?= Html::encode($tag['name']) ?></a>
        <?php endforeach; ?>
        <p><a href="<?= $urlGenerator->generate('tag/list') ?>">所有标签</a></p>
    </div>
</div>

<?php if ($recentComments !== []): ?>
    <div class="widget widget-comments">
        <h3>最新评论</h3>
        <ul>
            <?php foreach ($recentComments as $comment): ?>
                <li>
                    <a href="<?= Html::encode((string)$comment['post_url']) ?>"><?= Html::encode($comment['nickname']) ?></a>
                    <p><?= Html::encode(mb_strimwidth((string)$comment['content'], 0, 80, '...', 'utf-8')) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
