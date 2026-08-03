<?php

declare(strict_types=1);

use App\Common\XUtils;
use Yiisoft\Html\Html;

/**
 * 墨刊文章详情：居中窄栏阅读 + 文首 + 作者卡 + 前后篇。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
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

$category = $categorySummary[(int)$post->cid] ?? null;
$postUrl = $post->getUrl($urlGenerator);
/** @var ?App\User\User $author */
$author = $post->getAuthor()->one();
?>

<?php if ($post->cover !== null && $post->cover !== ''): ?>
    <div class="article-cover-banner">
        <img src="<?= Html::encode($post->cover) ?>" alt="">
    </div>
<?php endif; ?>

<article id="post-<?= $post->id ?>" class="article-detail">
    <header>
        <h1 class="article-detail-title"><?= Html::encode($post->title) ?></h1>
        <div class="article-detail-meta">
            <?php if ($category !== null): ?>
                <a href="<?= Html::encode((string)$category['url']) ?>"><?= Html::encode($category['name']) ?></a>
            <?php endif; ?>
            <span><?= Html::encode($post->author_name) ?></span>
            <span><?= XUtils::xDateFormatter((int)$post->post_time) ?></span>
            <span><?= (int)$post->view_count ?> 浏览</span>
            <span><a href="#comments"><?= (int)$post->comment_count ?> 评论</a></span>
        </div>
    </header>

    <?php if ($post->status === App\Post\Post::STATUS_HIDDEN): ?>
        <div class="article-locked-tip">这是一篇隐藏的文章，仅显示摘要。</div>
    <?php endif; ?>

    <div class="article-detail-body">
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

    <?php if ($post->tags !== ''): ?>
        <div class="article-tags">标签：
            <?php foreach (explode(',', $post->tags) as $tagName): ?>
                <?php $tagName = trim($tagName); ?>
                <?php if ($tagName === ''): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <a href="<?= Html::encode($urlGenerator->generate('tag/show', ['name' => $tagName])) ?>"><?= Html::encode($tagName) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($author !== null): ?>
        <div class="author-card">
            <img src="<?= Html::encode(XUtils::getAvatar($aliases, (string)$author->email, 56)) ?>" width="56" height="56" alt="">
            <div>
                <p class="author-card-name">
                    <a href="<?= Html::encode($urlGenerator->generate('user/show', ['name' => $author->nickname])) ?>"><?= Html::encode($author->nickname) ?></a>
                </p>
                <p class="author-card-desc"><?= Html::encode(mb_strimwidth(strip_tags((string)$author->info), 0, 100, '...', 'utf-8')) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($previous !== null || $next !== null): ?>
        <nav class="related-nav">
            <?php if ($previous !== null): ?>
                <a href="<?= Html::encode((string)$previous->getUrl($urlGenerator)) ?>">« 上一篇：<?= Html::encode($previous->title) ?></a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <?php if ($next !== null): ?>
                <a href="<?= Html::encode((string)$next->getUrl($urlGenerator)) ?>">下一篇：<?= Html::encode($next->title) ?> »</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
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
            <img src="<?= Html::encode($urlGenerator->generate('tool/captcha')) ?>" alt="验证码" class="auth-captcha">
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
