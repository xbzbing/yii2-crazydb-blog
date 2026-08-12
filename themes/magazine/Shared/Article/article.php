<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 墨刊文章卡片（网格）。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Post\Post $post
 * @var array{name: string, desc: ?string, url: ?string, postCount: int} $category
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Post\MarkdownRenderer $markdownRenderer
 */
$postUrl = $post->getUrl($urlGenerator);
?>

<article id="post-<?= $post->id ?>" class="article-card">
    <?php if ($post->status === App\Post\Post::STATUS_HIDDEN): ?>
        <div class="article-card-meta"><span class="article-locked">隐藏文章</span></div>
    <?php endif; ?>
    <?php if ($post->cover !== null && $post->cover !== ''): ?>
        <div class="article-card-cover">
            <img src="<?= Html::encode($post->cover) ?>" alt="<?= Html::encode($post->title) ?>">
        </div>
    <?php endif; ?>
    <h2 class="article-card-title">
        <a href="<?= Html::encode((string)$postUrl) ?>"><?= Html::encode($post->title) ?></a>
    </h2>
    <?php if ($post->excerpt !== null && $post->excerpt !== ''): ?>
        <p class="article-card-excerpt"><?= Html::encode(mb_strimwidth(strip_tags($post->excerpt), 0, 90, '...', 'utf-8')) ?></p>
    <?php endif; ?>
    <div class="article-card-meta">
        <span class="article-category"><?= Html::encode((string)$category['name']) ?></span>
        <span><?= Html::encode($post->author_name) ?></span>
        <span><?= App\Common\XUtils::xDateFormatter((int)$post->post_time) ?></span>
    </div>
</article>
