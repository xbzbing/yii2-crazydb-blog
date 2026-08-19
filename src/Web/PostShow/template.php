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
 * @var bool $unlocked
 * @var bool $passwordError
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
    (string)$post->password !== ''
        ? (string)$post->excerpt
        : $post->getSeoDescription($markdownRenderer),
);

$postUrl = $post->getUrl($urlGenerator);
$category = $categorySummary[(int)$post->cid] ?? null;

// highlight.js（复用 vditor 内置资源）
// 触发条件：Vditor 新格式 <pre><code>，或 UEditor 老格式 <pre class="brush:xxx">
if (str_contains($contentHtml, '<pre><code') || str_contains($contentHtml, 'brush:')) {
    $hlBase = '/static/vditor/dist/js/highlight.js';
    $this->registerCssFile($hlBase . '/styles/github.css');
    $this->registerJsFile($hlBase . '/highlight.pack.js');
    $this->registerJs(
        <<<'JS'
        (function () {
            // 语言映射：UEditor brush:xxx → highlight.js 语言名
            // 注意与 HtmlToMarkdownService::BRUSH_MAP 保持一致（前台展示与后台预览同源）
            var brushMap = {
                'plain': 'plaintext', 'text': 'plaintext', 'bash': 'bash', 'shell': 'bash', 'sh': 'bash',
                'php': 'php', 'python': 'python', 'py': 'python', 'java': 'java', 'c': 'c', 'cpp': 'cpp',
                'csharp': 'csharp', 'js': 'javascript', 'javascript': 'javascript', 'sql': 'sql', 'xml': 'xml',
                'html': 'xml', 'css': 'css', 'less': 'less', 'scss': 'scss', 'json': 'json', 'ruby': 'ruby',
                'go': 'go', 'typescript': 'typescript', 'ts': 'typescript'
            };
            document.querySelectorAll('pre code').forEach(function (b) {
                hljs.highlightBlock(b);
            });
            // UEditor 老格式：<pre class="brush:php;toolbar:false">（无 <code> 子元素）
            document.querySelectorAll('pre[class*="brush:"]').forEach(function (pre) {
                if (pre.querySelector('code')) return; // 已被上面处理
                var m = /brush:\s*([\w-]+)/.exec(pre.className);
                var lang = m ? (brushMap[m[1]] || m[1]) : '';
                var code = document.createElement('code');
                code.textContent = pre.textContent;
                if (lang) code.className = 'language-' + lang;
                pre.textContent = '';
                pre.appendChild(code);
                hljs.highlightBlock(code);
            });
        })();
        JS,
        \Yiisoft\View\WebView::POSITION_READY,
    );
}
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
            <?php if ((int)$post->is_top === 1): ?> <span class="article-top">置顶</span><?php endif; ?>
        </h1>
        <div class="article-meta">
            <span>作者：<?= Html::encode($post->author_name !== '' ? $post->author_name : ($authorNickname ?? '')) ?></span>
            <span><?= date('Y-m-d H:i', (int)$post->post_time) ?></span>
            <span><?= (int)$post->view_count ?> 浏览</span>
            <span>
                <a href="#comments"><?= (int)$post->comment_count ?> 评论</a>
            </span>
        </div>
    </header>
    <div class="article-card-content article-body">
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
        <?php if ((string)$post->password !== '' && !$unlocked): ?>
            <div class="article-locked-tip">
                <?php if ($passwordError): ?>
                    <div class="alert alert-danger" role="alert"><i class="fa-solid fa-circle-exclamation"></i> 密码错误，请重新输入。</div>
                <?php endif; ?>
                <?php if ($passwordLocked): ?>
                    <div class="alert alert-warning" role="alert"><i class="fa-solid fa-clock"></i> 尝试次数过多，请 15 分钟后再试。</div>
                <?php endif; ?>
                <div class="label label-warning"><i class="fa-solid fa-lock"></i> 该文章受密码保护，输入密码后可查看全文。</div>
                <form method="post" action="<?= Html::encode((string)($post->getUrl($urlGenerator) ?? '')) ?>" class="hidden-post-form">
                    <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" placeholder="请输入访问密码" required="required" autocomplete="off">
                        <span class="input-group-btn">
                            <button type="submit" class="btn btn-primary">解锁</button>
                        </span>
                    </div>
                </form>
            </div>
        <?php endif; ?>
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
        <?php $replyTo = isset($comment->reply_to) ? ($replyMap[(int)$comment->reply_to] ?? null) : null; ?>
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
