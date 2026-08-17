<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 分类文章列表页（等价 Yii2 views/category/show.php）。
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
 * @var App\Category\Category $category
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
$this->setTitle($category->name . ' - ' . $siteName);
$this->setParameter('seo_keywords', (string)($category->keywords !== '' ? $category->keywords : ($seoConfig['seo_keywords'] ?? '')));
$this->setParameter('seo_description', (string)($category->desc ?? ''));
?>

<nav class="breadcrumb">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » <?= Html::encode($category->name) ?>
</nav>

<article class="article-card">
    <header class="article-card-header">
        <h1><?= Html::encode($category->name) ?></h1>
        <?php if ($category->desc !== null && $category->desc !== ''): ?>
            <p class="article-meta"><?= Html::encode($category->desc) ?></p>
        <?php endif; ?>
    </header>
</article>

<?= $this->render('PostList/postList.php', [
    'posts' => $posts,
    'pager' => $pager,
    'markdownRenderer' => $markdownRenderer,
    'urlGenerator' => $urlGenerator,
    'categorySummary' => $categorySummary,
    'emptyText' => '该分类下暂无文章。',
    'route' => 'category/show',
    'pageRoute' => 'category/show-page',
    'routeArgs' => ['alias' => $category->alias],
]) ?>
