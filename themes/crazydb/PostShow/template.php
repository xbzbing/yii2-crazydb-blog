<?php

declare(strict_types=1);

use App\Common\XUtils;
use Yiisoft\Html\Html;

/**
 * Crazydb 主题文章详情页（忠实还原线上 post/view.php：
 * entry-meta 分类+标签 / 一年警告 / 较新较旧 footer / 留言交流评论树 / input-group 表单）。
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
$isOld = (int)$post->post_time <= strtotime('-1 years');
?>

<div class="breadcrumbs with-shadow">
    <i class="fa-solid fa-location-dot"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    <?php if ($category !== null && $category['url'] !== null): ?>
        » <a href="<?= Html::encode((string)$category['url']) ?>"><?= Html::encode((string)$category['name']) ?></a>
    <?php endif; ?>
    » <?= Html::encode($post->title) ?>
</div>

<article id="post-<?= $post->id ?>" class="post-view">
    <header class="entry-header">
        <h1><?= Html::encode($post->title) ?></h1>
    </header>
    <div class="entry-meta">
        <?php if ($category !== null && $category['url'] !== null): ?>
            <a href="<?= Html::encode((string)$category['url']) ?>" class="pl-category" title="<?= Html::encode((string)$category['name']) ?>">
                <span class="label label-info"><?= Html::encode((string)$category['name']) ?></span>
            </a>
        <?php endif; ?>
        <?php if ($post->tags !== ''): ?>
            <span class="label label-primary">
                <?php $tagList = array_filter(array_map('trim', explode(',', $post->tags))); ?>
                <?php $tagCount = count($tagList); $tagIndex = 0; ?>
                <?php foreach ($tagList as $tagName): ?>
                    <?php $tagIndex++; ?>
                    <a href="<?= Html::encode($urlGenerator->generate('tag/show', ['name' => $tagName])) ?>" title="<?= Html::encode($tagName) ?>" class="text-white"><?= Html::encode($tagName) ?></a><?= $tagIndex < $tagCount ? ' / ' : '' ?>
                <?php endforeach; ?>
            </span>
        <?php endif; ?>
        <span><i class="fa-solid fa-user"></i>
            <a href="<?= Html::encode($authorUrl) ?>" title="<?= Html::encode($post->author_name) ?>"><?= Html::encode($post->author_name) ?></a>
        </span>
        <span><i class="fa-solid fa-clock"></i><?= XUtils::xDateFormatter((int)$post->post_time) ?></span>
        <span><i class="fa-solid fa-eye"></i><?= (int)$post->view_count ?> 浏览</span>
        <span>
            <a href="#comments" title="查看评论"><span class="badge"><?= (int)$post->comment_count ?> 评论</span></a>
        </span>
    </div>
    <div class="entry-content">
        <?php if ($isOld): ?>
            <div class="alert alert-warning">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <span>这篇日志发布时间已经超过一年，许多内容可能已经失效，请读者酌情参考。</span>
            </div>
        <?php endif; ?>
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
        <?php if ($post->status === App\Post\Post::STATUS_HIDDEN): ?>
            <div class="label label-warning">这是一篇隐藏的文章，需要输入密码才能查看全文。</div>
        <?php endif; ?>
        <?= $contentHtml ?>
    </div>
    <footer class="entry-footer">
        <?php if ($next !== null): ?>
            <h4 class="float-start"><i class="fa-solid fa-chevron-left"></i>
                <a href="<?= Html::encode((string)$next->getUrl($urlGenerator)) ?>" title="较新的一篇"><?= Html::encode($next->title) ?></a>
            </h4>
        <?php else: ?>
            <h4 class="float-start"><small><i class="fa-solid fa-chevron-up"></i> 已是最新的文章</small></h4>
        <?php endif; ?>
        <?php if ($previous !== null): ?>
            <h4 class="float-end">
                <a href="<?= Html::encode((string)$previous->getUrl($urlGenerator)) ?>" title="较旧的一篇"><?= Html::encode($previous->title) ?></a>
                <i class="fa-solid fa-chevron-right"></i>
            </h4>
        <?php else: ?>
            <h4 class="float-end"><small>已是最后一篇文章 <i class="fa-solid fa-chevron-up"></i></small></h4>
        <?php endif; ?>
    </footer>
</article>

<div class="post-comments">
    <h3 id="comments">留言交流</h3>
    <div class="comment-list">
        <?php if ($comments === []): ?>
            <div id="no-comment">没有评论</div>
        <?php endif; ?>
        <?php foreach ($comments as $comment): ?>
            <?php $replyTo = isset($comment->reply_to) ? ($replyMap[(int)$comment->reply_to] ?? null) : null; ?>
            <div id="comment-<?= $comment->id ?>" class="comment-panel<?= $replyTo !== null ? ' reply-to' : '' ?>">
                <div class="avatar">
                    <img class="img-thumbnail" src="<?= Html::encode(XUtils::getAvatar($aliases, (string)$comment->email, 40)) ?>" width="40" alt="<?= Html::encode($comment->nickname) ?>">
                </div>
                <div class="comment-body">
                    <div class="comment-meta">
                        <span class="name"><?= Html::encode($comment->nickname) ?></span>
                        <span class="time"><i class="fa-solid fa-clock"></i> <?= XUtils::xDateFormatter((int)$comment->create_time) ?></span>
                        <a class="comment-reply-link" data-reply-to="<?= $comment->id ?>" data-reply-name="<?= Html::encode($comment->nickname) ?>">回复</a>
                    </div>
                    <div class="comment-content">
                        <?php if ($replyTo !== null): ?>
                            <span class="replyTarget">回复 <a href="#comment-<?= (int)$replyTo->id ?>"><em><?= Html::encode((string)$replyTo->nickname) ?></em></a> : </span>
                        <?php endif; ?>
                        <?= Html::encode((string)$comment->content) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="comment-area">
        <div class="comment" id="comment">
            <form class="leave-comment row" method="post" action="<?= Html::encode($urlGenerator->generate('comment/add', ['id' => $post->id])) ?>">
                <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                <input type="hidden" name="reply_to" value="0" id="parentId">
                <div class="col-md-4">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" required="required" class="form-control" name="nickname" placeholder="* 昵称">
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" required="required" class="form-control" name="email" placeholder="* 邮箱">
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fa-solid fa-globe"></i></span>
                        <input type="text" maxlength="80" class="form-control" name="url" placeholder="网站地址">
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text captcha-cover">
                            <img src="<?= Html::encode($urlGenerator->generate('tool/captcha')) ?>" alt="点击换图" title="点击换图" style="cursor:pointer" height="32">
                        </span>
                        <input type="text" required="required" class="form-control" name="captcha" placeholder="验证码">
                    </div>
                </div>
                <div class="col-md-8">
                    <textarea class="form-control" required="required" name="content" id="comment-content" rows="5" placeholder="留下你的看法，欢迎交流 :)"></textarea>
                    <p id="comment-form-hint" class="comment-reply-hint"></p>
                    <div class="actionPanel">
                        <button type="submit" class="btn btn-primary">提交留言 <i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                </div>
                <div class="clearfix"></div>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-reply-to]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('parentId').value = btn.dataset.replyTo;
            document.getElementById('comment-form-hint').textContent = '正在回复 @' + btn.dataset.replyName + '（可取消）';
            window.scrollTo({top: document.getElementById('comment-area').offsetTop - 80, behavior: 'smooth'});
        });
    });
</script>
