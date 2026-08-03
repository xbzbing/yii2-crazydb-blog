<?php

use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Web\Pager;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var list<Post> $posts
 * @var Pager $pager
 * @var MarkdownRenderer $markdownRenderer
 * @var UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var array<int, array{name: string, desc: ?string, url: ?string, postCount: int}> $categorySummary
 * @var list<array{totalCount: int, name: string, create_time: int, url: string}> $sidebarTags
 * @var list<array{id: ?int, nickname: string, website: ?string, pid: ?int, post_url: ?string, content: ?string, create_time: ?int, email: string, avatar: string, title: string}> $sidebarComments
 */

$this->setParameter('categorySummary', $categorySummary);
$this->setParameter('sidebarTags', $sidebarTags);
$this->setParameter('sidebarComments', $sidebarComments);
$this->setParameter('showSidebar', $showSidebar);

$this->setTitle((string)($siteConfig['site_name'] ?? $applicationParams->name));
$this->setParameter('seo_keywords', (string)($siteConfig['seo_keywords'] ?? ''));
$this->setParameter('seo_description', (string)($siteConfig['seo_description'] ?? ''));

$categories = $categorySummary;
?>

<?php if ($posts === []): ?>
    <article class="article-card"><h1>暂时没有公开的文章发布，请关注本站更新！</h1></article>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <?= $this->render('Article/article.php', [
            'post' => $post,
            'category' => $categories[(int)$post->cid] ?? ['name' => '', 'desc' => null, 'url' => null, 'postCount' => 0],
            'urlGenerator' => $urlGenerator,
            'markdownRenderer' => $markdownRenderer,
        ]) ?>
    <?php endforeach; ?>
<?php endif; ?>

<?= $this->render('Pager/pager.php', [
    'pager' => $pager,
    'urlGenerator' => $urlGenerator,
    'route' => 'site/index',
]) ?>
