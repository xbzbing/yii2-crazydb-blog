<?php


use Yiisoft\Html\Html;

/**
 * 文章卡片（等价 Yii2 views/post/_article.php）。
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
    <header class="article-card-header">
        <h3>
            <a href="<?= Html::encode((string)$category['url']) ?>" class="article-category">
                <?= Html::encode((string)$category['name']) ?>
            </a>
            <?php if ((string)$post->password !== ''): ?>
                <span class="article-locked">密码保护</span>
            <?php endif; ?>
            <a class="article-title" href="<?= Html::encode((string)$postUrl) ?>" title="<?= Html::encode($post->title) ?>" rel="bookmark">
                <?= Html::encode($post->title) ?>
            </a>
        </h3>
    </header>
    <div class="article-card-content">
        <?php if ((string)$post->password !== ''): ?>
            <div class="article-locked-tip">该文章需要输入密码才能查看全文。</div>
        <?php endif; ?>
        <?= $post->excerpt !== null && $post->excerpt !== ''
            ? \App\Common\XUtils::htmlPurify($post->excerpt)
            : '' ?>
        <div class="article-read-more">
            <a href="<?= Html::encode((string)$postUrl) ?>" title="<?= Html::encode($post->title) ?>">阅读全文</a>
        </div>
    </div>
    <footer class="article-card-footer">
        <span>作者：<?= Html::encode($post->author_name) ?></span>
        <span><?= App\Common\XUtils::xDateFormatter((int)$post->post_time) ?></span>
        <span><?= (int)$post->view_count ?> 浏览</span>
        <span>
            <a href="<?= Html::encode((string)$postUrl) ?>#comments">
                <?= (int)$post->comment_count > 0 ? (int)$post->comment_count . ' 评论' : '抢沙发' ?>
            </a>
        </span>
    </footer>
</article>
