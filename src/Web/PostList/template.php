<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 所有文章列表页（等价 Yii2 views/post/posts.php）。
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
 * @var list<App\Post\Post> $posts
 * @var App\Web\Pager $pager
 */

$this->setParameter('categorySummary', $categorySummary);
$this->setParameter('sidebarTags', $sidebarTags);
$this->setParameter('sidebarComments', $sidebarComments);
$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('所有文章 - ' . $siteName);
$this->setParameter('seo_keywords', (string)($seoConfig['seo_keywords'] ?? ''));
$this->setParameter('seo_description', (string)($seoConfig['seo_description'] ?? ''));
?>

<nav class="breadcrumb">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 所有文章
</nav>

<article class="article-card">
    <header class="article-card-header">
        <h1>所有文章</h1>
    </header>
</article>

<?= $this->render('PostList/postList.php', [
    'posts' => $posts,
    'pager' => $pager,
    'markdownRenderer' => $markdownRenderer,
    'urlGenerator' => $urlGenerator,
    'categorySummary' => $categorySummary,
    'emptyText' => '暂时没有公开的文章发布，请关注本站更新！',
    'route' => 'post/list',
    'pageRoute' => 'post/list-page',
    'routeArgs' => [],
]) ?>
