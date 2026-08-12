<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 右侧边栏（等价 Yii2 right-aside + aside-* 组件）。
 *
 * @var array<string, mixed> $siteConfig
 * @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $categorySummary
 * @var list<array{totalCount: int, name: string, create_time: int, url: string}> $tags
 * @var list<array{id: int, nickname: string, website: ?string, pid: int, post_url: ?string, content: ?string, create_time: int, email: string, avatar: string, title: string}> $recentComments
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */
?>

<div class="widget widget-about">
    <h3>关于我</h3>
    <p>坚持写博客，记录技术点滴与生活思考。</p>
    <p><?= Html::encode((string)($siteConfig['admin_email'] ?? '')) ?></p>
</div>

<div class="widget widget-categories">
    <h3>
        分类文章
        <a href="<?= $urlGenerator->generate('feed/rss') ?>" target="_blank" title="RSS订阅">RSS</a>
    </h3>
    <ul>
        <?php foreach ($categorySummary as $category): ?>
            <?php if ($category['postCount'] < 1) {
                continue;
            } ?>
            <li>
                <a href="<?= Html::encode((string)$category['url']) ?>" title="<?= Html::encode($category['desc'] ?: $category['name']) ?>">
                    <?= Html::encode($category['name']) ?>
                </a>
                （<?= $category['postCount'] ?> 篇）
            </li>
        <?php endforeach; ?>
        <li><a href="<?= $urlGenerator->generate('post/archives') ?>" target="_blank">文章归档</a></li>
    </ul>
</div>

<div class="widget widget-tags">
    <h3>标签</h3>
    <div class="tag-cloud">
        <?php if ($tags === []): ?>
            <p>暂无标签</p>
        <?php endif; ?>
        <?php foreach ($tags as $tag): ?>
            <a href="<?= Html::encode($tag['url']) ?>" title="<?= Html::encode($tag['name']) ?>（<?= $tag['totalCount'] ?>）" target="_blank">
                <?= Html::encode($tag['name']) ?>
            </a>
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
                <img src="<?= Html::encode($comment['avatar']) ?>" width="40" height="40" alt="<?= Html::encode($comment['nickname']) ?>">
                <a href="<?= Html::encode((string)$comment['post_url']) ?>"><?= Html::encode($comment['nickname']) ?></a>
                <p><?= App\Common\XUtils::strimwidthWithTag((string)$comment['content'], 0, 120) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
