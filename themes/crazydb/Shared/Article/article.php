<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Crazydb 主题文章卡片（还原线上 list-group-item + entry-header/content/footer）。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Post\Post $post
 * @var array{name: string, desc: ?string, url: ?string, postCount: int} $category
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Post\MarkdownRenderer $markdownRenderer
 */
$postUrl = $post->getUrl($urlGenerator);
?>

<article id="post-<?= $post->id ?>" class="list-group-item">
    <header class="entry-header">
        <h3>
            <?php if ($category['url'] !== null && $category['name'] !== ''): ?>
                <a href="<?= Html::encode((string)$category['url']) ?>" class="pl-category" title="<?= Html::encode((string)$category['name']) ?>">
                    <span class="label label-info"><?= Html::encode((string)$category['name']) ?></span>
                </a>
            <?php endif; ?>
            <?php if ((string)$post->password !== ''): ?>
                <span class="label label-warning" title="该文章需要密码访问"><i class="fa-solid fa-lock"></i></span>
            <?php endif; ?>
            <?php if ((int)$post->is_top === 1): ?>
                <span class="label label-danger post-top-label">置顶</span>
            <?php endif; ?>
            <a class="pl-title" href="<?= Html::encode((string)$postUrl) ?>" title="<?= Html::encode($post->title) ?>" rel="bookmark">
                <?= Html::encode($post->title) ?>
            </a>
        </h3>
    </header>
    <div class="entry-content">
        <?php if ((string)$post->password !== ''): ?>
            <div class="label label-warning">该文章需要输入密码才能查看全文。</div>
        <?php endif; ?>
        <?= $post->excerpt !== null && $post->excerpt !== ''
            ? \App\Common\XUtils::htmlPurify($post->excerpt)
            : '' ?>
        <div class="float-end">
            <a href="<?= Html::encode((string)$postUrl) ?>" title="<?= Html::encode($post->title) ?>" class="read-more">
                <i class="fa-solid fa-arrow-up-right-from-square"></i><span>阅读全文</span>
            </a>
        </div>
    </div>
    <footer class="entry-footer">
        <span><i class="fa-solid fa-user"></i><a href="<?= Html::encode($urlGenerator->generate('user/show', ['name' => $post->author_name])) ?>"><?= Html::encode($post->author_name) ?></a></span>
        <span><i class="fa-solid fa-clock"></i><?= \App\Common\XUtils::xDateFormatter((int)$post->post_time) ?></span>
        <span><i class="fa-solid fa-eye"></i><?= (int)$post->view_count ?> 浏览</span>
        <span>
            <a href="<?= Html::encode((string)$postUrl) ?>#comments">
                <span class="badge"><?= (int)$post->comment_count > 0 ? (int)$post->comment_count . ' 评论' : '抢沙发' ?></span>
            </a>
        </span>
    </footer>
</article>
