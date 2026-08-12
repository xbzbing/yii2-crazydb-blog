<?php

declare(strict_types=1);

use App\Common\XUtils;
use Yiisoft\Html\Html;

/**
 * Crazydb 主题文章详情页（还原线上：breadcrumbs + #content + comment-list/leave-comment）。
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
 * @var list<array{id: string, level: int, text: string}> $toc
 * @var list<App\Comment\Comment> $comments
 * @var array<int, App\Comment\Comment> $replyMap
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
$authorUrl = $urlGenerator->generate('user/show', ['name' => $post->author_name]);
?>

<div class="breadcrumbs">
    <i class="fa-solid fa-angle-right"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    <?php if ($category !== null && $category['url'] !== null): ?>
        » <a href="<?= Html::encode((string)$category['url']) ?>"><?= Html::encode((string)$category['name']) ?></a>
    <?php endif; ?>
    » <?= Html::encode($post->title) ?>
</div>

<div id="content" class="with-shadow">
    <article id="post-<?= $post->id ?>" class="post-view">
        <header class="entry-header">
            <h1><?= Html::encode($post->title) ?></h1>
        </header>
        <div class="entry-meta">
            <span><i class="fa-solid fa-user"></i><a href="<?= Html::encode($authorUrl) ?>"><?= Html::encode($post->author_name) ?></a></span>
            <span><i class="fa-solid fa-clock"></i><?= XUtils::xDateFormatter((int)$post->post_time) ?></span>
            <span><i class="fa-solid fa-eye"></i><?= (int)$post->view_count ?> 浏览</span>
            <span><a href="#comments"><i class="fa-solid fa-comment"></i><?= (int)$post->comment_count ?> 评论</a></span>
            <?php if ($post->status === App\Post\Post::STATUS_HIDDEN): ?>
                <span class="badge text-bg-warning">隐藏文章</span>
            <?php endif; ?>
        </div>
        <div class="entry-content">
            <?php if ($toc !== []): ?>
                <details class="article-toc">
                    <summary>目录</summary>
                    <ul>
                        <?php foreach ($toc as $item): ?>
                            <li class="toc-level-<?= $item['level'] ?>">
                                <a href="#<?= Html::encode($item['id']) ?>"><?= Html::encode($item['text']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>
            <?= $contentHtml ?>
        </div>
        <footer class="entry-footer">
            <?php if ($post->tags !== ''): ?>
                <span>标签：
                    <?php foreach (explode(',', $post->tags) as $tagName): ?>
                        <?php $tagName = trim($tagName); ?>
                        <?php if ($tagName === ''): ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <span class="badge text-bg-secondary"><a href="<?= Html::encode($urlGenerator->generate('tag/show', ['name' => $tagName])) ?>" class="text-white"><?= Html::encode($tagName) ?></a></span>
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

    <section id="comments" class="post-comments">
        <h3><?= $commentTotal > 0 ? "评论（{$commentTotal}）" : '评论' ?></h3>
        <?php if ($comments === []): ?>
            <p>暂时还没有评论，赶紧抢沙发吧！</p>
        <?php endif; ?>
        <div class="comment-list">
            <?php foreach ($comments as $i => $comment): ?>
                <?php $replyTo = isset($comment->reply_to) ? ($replyMap[(int)$comment->reply_to] ?? null) : null; ?>
                <div id="comment-<?= $comment->id ?>" class="comment-panel">
                    <img src="<?= Html::encode(XUtils::getAvatar($aliases, (string)$comment->email, 40)) ?>" width="40" height="40" class="avatar img-thumbnail" alt="">
                    <div class="comment-body">
                        <div class="comment-meta">
                            <strong><?= Html::encode($comment->nickname) ?></strong>
                            <span><?= XUtils::xDateFormatter((int)$comment->create_time) ?></span>
                            <span>#<?= $i + 1 ?></span>
                            <?php if ($replyTo !== null): ?>
                                <span>回复 @<?= Html::encode((string)$replyTo->nickname) ?></span>
                            <?php endif; ?>
                            <a class="comment-reply-link" data-reply-to="<?= $comment->id ?>" data-reply-name="<?= Html::encode($comment->nickname) ?>">回复</a>
                        </div>
                        <div class="comment-content">
                            <?= Html::encode((string)$comment->content) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="leave-comment">
        <h3>发表评论</h3>
        <p id="comment-form-hint" class="comment-reply-hint"></p>
        <form class="comment-form row" method="post" action="<?= Html::encode($urlGenerator->generate('comment/add', ['id' => $post->id])) ?>">
            <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
            <input type="hidden" name="reply_to" id="comment-reply-to" value="">
            <div class="col-md-4">
                <label>昵称：<input class="form-control" type="text" name="nickname" required></label>
                <label>邮箱：<input class="form-control" type="email" name="email" required></label>
                <label>网址：<input class="form-control" type="text" name="url" placeholder="http:// 或 https://"></label>
            </div>
            <div class="col-md-8">
                <label>内容：<textarea class="form-control" name="content" required></textarea></label>
                <label>验证码：
                    <img src="<?= Html::encode($urlGenerator->generate('tool/captcha')) ?>" alt="验证码">
                    <input class="form-control" type="text" name="captcha" required>
                </label>
            </div>
            <div class="col-12 actionPanel">
                <button type="submit" class="btn btn-primary">提交留言</button>
            </div>
        </form>
    </section>
</div>

<script>
    document.querySelectorAll('[data-reply-to]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('comment-reply-to').value = btn.dataset.replyTo;
            document.getElementById('comment-form-hint').textContent = '正在回复 @' + btn.dataset.replyName + '（可取消）';
            window.scrollTo({top: document.querySelector('.comment-form').offsetTop - 80, behavior: 'smooth'});
        });
    });
</script>
