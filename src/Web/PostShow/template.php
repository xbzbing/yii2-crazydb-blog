<?php

declare(strict_types=1);

use App\Common\XUtils;
use Yiisoft\Html\Html;

/**
 * 文章详情页（等价 Yii2 views/post/view.php）。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var list<App\Post\Post> $posts
 * @var App\Web\Pager $pager
 * @var App\Post\MarkdownRenderer $markdownRenderer
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, string|null> $seoConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $categorySummary
 * @var list<array{totalCount: int, name: string, create_time: int, url: string}> $sidebarTags
 * @var list<array{id: ?int, nickname: string, website: ?string, pid: ?int, post_url: ?string, content: ?string, create_time: ?int, email: string, avatar: string, title: string}> $sidebarComments
 * @var App\Post\Post $post
 * @var string $contentHtml
 * @var list<App\Comment\Comment> $comments
 * @var int $commentTotal
 * @var ?App\Post\Post $previous
 * @var ?App\Post\Post $next
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var ?string $csrf
 */

$this->setParameter('categorySummary', $categorySummary);
$this->setParameter('sidebarTags', $sidebarTags);
$this->setParameter('sidebarComments', $sidebarComments);
$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle($post->title . ' - ' . $siteName);
$this->setParameter('seo_keywords', (string)($post->tags !== '' ? $post->tags : ($seoConfig['seo_keywords'] ?? '')));
$this->setParameter(
    'seo_description',
    $post->status === App\Post\Post::STATUS_HIDDEN
        ? (string)$post->excerpt
        : $post->getSeoDescription($markdownRenderer),
);

$postUrl = $post->getUrl($urlGenerator);
$category = $categorySummary[(int)$post->cid] ?? null;
?>

<nav class="breadcrumb">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    <?php if ($category !== null): ?>
        » <a href="<?= Html::encode((string)$category['url']) ?>"><?= Html::encode($category['name']) ?></a>
    <?php endif; ?>
    » <?= Html::encode($post->title) ?>
</nav>

<article id="post-<?= $post->id ?>" class="article-card article-detail">
    <header class="article-card-header">
        <h1 class="article-title">
            <?= Html::encode($post->title) ?>
        </h1>
        <div class="article-meta">
            <span>作者：<?= Html::encode($post->author_name) ?></span>
            <span><?= XUtils::xDateFormatter((int)$post->post_time) ?></span>
            <span><?= (int)$post->view_count ?> 浏览</span>
            <span>
                <a href="#comments"><?= (int)$post->comment_count ?> 评论</a>
            </span>
        </div>
    </header>
    <div class="article-card-content article-body">
        <?= $contentHtml ?>
    </div>
    <footer class="article-card-footer">
        <?php if ($post->tags !== ''): ?>
            <span>标签：
                <?php foreach (explode(',', $post->tags) as $tagName): ?>
                    <?php $tagName = trim($tagName); ?>
                    <?php if ($tagName === ''): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <a href="<?= Html::encode($urlGenerator->generate('tag/show', ['name' => $tagName])) ?>"><?= Html::encode($tagName) ?></a>
                <?php endforeach; ?>
            </span>
        <?php endif; ?>
        <?php if ($previous !== null): ?>
            <span class="article-related">上一篇：<a href="<?= Html::encode((string)$previous->getUrl($urlGenerator)) ?>"><?= Html::encode($previous->title) ?></a></span>
        <?php endif; ?>
        <?php if ($next !== null): ?>
            <span class="article-related">下一篇：<a href="<?= Html::encode((string)$next->getUrl($urlGenerator)) ?>"><?= Html::encode($next->title) ?></a></span>
        <?php endif; ?>
    </footer>
</article>

<section id="comments" class="comments">
    <h3><?= $commentTotal > 0 ? "评论（{$commentTotal}）" : '评论' ?></h3>
    <?php if ($comments === []): ?>
        <p>暂时还没有评论，赶紧抢沙发吧！</p>
    <?php endif; ?>
    <?php foreach ($comments as $i => $comment): ?>
        <?php $replyTo = $comment->isReply() ? $comment->getReply() : null; ?>
        <div id="comment-<?= $comment->id ?>" class="comment">
            <div class="comment-meta">
                <img src="<?= Html::encode(XUtils::getAvatar($aliases, (string)$comment->email, 32)) ?>" width="32" height="32" alt="">
                <strong><?= Html::encode($comment->nickname) ?></strong>
                <span><?= XUtils::xDateFormatter((int)$comment->create_time) ?></span>
                <span>#<?= $i + 1 ?></span>
                <?php if ($replyTo !== null): ?>
                    <span>回复 @<?= Html::encode((string)$replyTo->nickname) ?></span>
                <?php endif; ?>
                <button type="button" class="link-button" data-reply-to="<?= $comment->id ?>" data-reply-name="<?= Html::encode($comment->nickname) ?>">回复</button>
            </div>
            <div class="comment-content">
                <?= Html::encode((string)$comment->content) ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<section class="comments">
    <h3>发表评论</h3>
    <p id="comment-form-hint" class="comment-reply-hint"></p>
    <form class="comment-form" method="post" action="<?= Html::encode($urlGenerator->generate('comment/add', ['id' => $post->id])) ?>">
        <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
        <input type="hidden" name="reply_to" id="comment-reply-to" value="">
        <label>昵称：<input type="text" name="nickname" required></label>
        <label>邮箱：<input type="email" name="email" required></label>
        <label>网址：<input type="text" name="url" placeholder="http:// 或 https://"></label>
        <label>内容：<textarea name="content" required></textarea></label>
        <label>验证码：
            <img src="<?= Html::encode($urlGenerator->generate('tool/captcha')) ?>" alt="验证码">
            <input type="text" name="captcha" required>
        </label>
        <button type="submit">提交留言</button>
    </form>
    <script>
        document.querySelectorAll('[data-reply-to]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('comment-reply-to').value = btn.dataset.replyTo;
                document.getElementById('comment-form-hint').textContent = '正在回复 @' + btn.dataset.replyName + '（可取消）';
                window.scrollTo({top: document.querySelector('.comment-form').offsetTop - 80, behavior: 'smooth'});
            });
        });
    </script>
</section>
