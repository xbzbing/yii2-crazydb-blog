<?php

declare(strict_types=1);

use App\Common\XUtils;
use Yiisoft\Html\Html;

/**
 * Crazydb 主题用户主页（还原线上 user/view.php：
 * breadcrumbs + author-info 个人资料卡片 + 文章分页列表）。
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

<div class="breadcrumbs">
    <i class="fa-solid fa-location-dot"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 用户
    » <?= Html::encode($user->nickname) ?>
</div>

<div id="content">
    <div class="user-profile-card">
        <div class="user-avatar">
            <img src="<?= Html::encode(XUtils::getAvatar($aliases, (string)$user->email, 96)) ?>" width="96" height="96" alt="<?= Html::encode($user->nickname) ?>">
        </div>
        <div class="user-meta">
            <h3 class="user-nickname"><?= Html::encode($user->nickname) ?></h3>
            <div class="user-detail">
                <span><i class="fa-solid fa-calendar-days"></i> 注册于 <?= XUtils::xDateFormatter((int)$user->register_time) ?></span>
                <?php if ($user->website !== null && $user->website !== ''): ?>
                    <span><a href="<?= Html::encode($user->website) ?>" rel="nofollow noopener" target="_blank"><i class="fa-solid fa-globe"></i> 个人网站</a></span>
                <?php endif; ?>
                <?php $viewer = $authService->currentUser(); ?>
                <?php if ($viewer !== null && (int)$viewer->id === (int)$user->id): ?>
                    <span><a href="<?= Html::encode($urlGenerator->generate('user/profile-edit')) ?>"><i class="fa-solid fa-user-pen"></i> 修改资料</a></span>
                    <span><a href="<?= Html::encode($urlGenerator->generate('user/modify-password')) ?>"><i class="fa-solid fa-key"></i> 修改密码</a></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($user->info !== null && $user->info !== ''): ?>
            <div class="user-info-body"><?= $user->info ?></div>
        <?php endif; ?>
    </div>

    <div class="user-post-count">
        <?= Html::encode($user->nickname) ?> 共发表 <strong><?= (int)$pager->totalCount ?></strong> 篇文章。
    </div>

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
</div>
