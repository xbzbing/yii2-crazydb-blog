<?php

declare(strict_types=1);

use App\Common\XUtils;
use Yiisoft\Html\Html;

/**
 * 用户主页（等价 Yii2 views/user/view.php）。
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
 * @var App\User\User $user
 * @var list<App\Post\Post> $posts
 * @var App\Web\Pager $pager
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var App\User\AuthService $authService
 */

$this->setParameter('categorySummary', $categorySummary);
$this->setParameter('sidebarTags', $sidebarTags);
$this->setParameter('sidebarComments', $sidebarComments);
$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle($user->nickname . ' - ' . $siteName);
$this->setParameter('seo_keywords', (string)($seoConfig['seo_keywords'] ?? ''));
$seoDescription = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$user->info)) ?? '');
$this->setParameter(
    'seo_description',
    $user->nickname . '在「' . $siteName . '」共发表' . $pager->totalCount . '篇文章，个人简介：' . mb_strimwidth($seoDescription, 0, 120, '...', 'utf-8'),
);
?>

<nav class="breadcrumb">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » <?= Html::encode($user->nickname) ?>
</nav>

<article class="article-card">
    <header class="article-card-header">
        <h1>
            <img src="<?= Html::encode(XUtils::getAvatar($aliases, (string)$user->email, 48)) ?>" width="48" height="48" alt="" class="user-avatar">
            <?= Html::encode($user->nickname) ?>
        </h1>
        <div class="article-meta">
            <span>注册于 <?= XUtils::xDateFormatter((int)$user->register_time) ?></span>
            <span>共 <?= (int)$pager->totalCount ?> 篇文章</span>
            <?php if ($user->website !== null && $user->website !== ''): ?>
                <span><a href="<?= Html::encode($user->website) ?>" rel="nofollow noopener" target="_blank">个人网站</a></span>
            <?php endif; ?>
            <?php $viewer = $authService->currentUser(); ?>
            <?php if ($viewer !== null && (int)$viewer->id === (int)$user->id): ?>
                <span><a href="<?= Html::encode($urlGenerator->generate('user/modify-password')) ?>">修改密码</a></span>
            <?php endif; ?>
        </div>
    </header>
    <?php if ($user->info !== null && $user->info !== ''): ?>
        <div class="article-card-content user-info">
            <?= $user->info ?>
        </div>
    <?php endif; ?>
</article>

<?= $this->render('PostList/postList.php', [
    'posts' => $posts,
    'pager' => $pager,
    'markdownRenderer' => $markdownRenderer,
    'urlGenerator' => $urlGenerator,
    'categorySummary' => $categorySummary,
    'emptyText' => '该用户还没有发布文章。',
    'route' => 'user/show',
    'pageRoute' => 'user/show-page',
    'routeArgs' => ['name' => $user->nickname],
]) ?>
