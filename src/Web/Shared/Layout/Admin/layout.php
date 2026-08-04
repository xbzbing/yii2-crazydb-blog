<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台布局（AdminLTE 4 / Bootstrap 5）。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var string $content
 * @var string|null $csrf
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\User\AuthService $authService
 * @var Yiisoft\Session\Flash\FlashInterface $flash
 */

$assetManager->register(\App\Web\Shared\Layout\Admin\AdminAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$currentUser = $authService->currentUser();
/** @var Yiisoft\Router\CurrentRoute|null $currentRoute */
$currentRoute = $this->getParameter('currentRoute', null);
$currentPath = $currentRoute?->getUri()?->getPath() ?? '';
$menu = [
    ['label' => '仪表盘', 'route' => 'admin/index', 'icon' => 'fa-gauge-high'],
    ['label' => '文章管理', 'route' => 'admin/post/list', 'icon' => 'fa-file-lines'],
    ['label' => '评论管理', 'route' => 'admin/comment/list', 'icon' => 'fa-comments'],
    ['label' => '分类管理', 'route' => 'admin/category/list', 'icon' => 'fa-folder-open'],
    ['label' => '导航管理', 'route' => 'admin/nav/list', 'icon' => 'fa-compass'],
    ['label' => '标签管理', 'route' => 'admin/tag/list', 'icon' => 'fa-tags'],
    ['label' => '用户管理', 'route' => 'admin/user/list', 'icon' => 'fa-users'],
    ['label' => '日志管理', 'route' => 'admin/log/list', 'icon' => 'fa-clock-rotate-left'],
    ['label' => '站点配置', 'route' => 'admin/config', 'icon' => 'fa-gear'],
];
$this->beginPage()
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->getTitle() !== '' ? $this->getTitle() : '后台管理') ?></title>
    <?php $this->head() ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<?php $this->beginBody() ?>

<div class="app-wrapper">
    <!-- 顶栏 -->
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="切换侧边栏">
                        <i class="bi bi-list"></i>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($currentUser !== null): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user me-1"></i><?= Html::encode($currentUser->nickname) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= $urlGenerator->generate('site/index') ?>"><i class="fa-solid fa-house me-2"></i>返回前台</a></li>
                            <li>
                                <form method="post" action="<?= $urlGenerator->generate('site/logout') ?>" class="m-0">
                                    <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                                    <button type="submit" class="dropdown-item"><i class="fa-solid fa-right-from-bracket me-2"></i>退出登录</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- 侧边栏 -->
    <aside class="app-sidebar bg-dark elevation-4" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="<?= $urlGenerator->generate('admin/index') ?>" class="brand-link">
                <span class="brand-text fw-light">Crazydb-Blog 后台</span>
            </a>
        </div>
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                <?php foreach ($menu as $item): ?>
                    <?php $menuUrl = $urlGenerator->generate($item['route']); ?>
                    <?php $isActive = $item['route'] === 'admin/index'
                        ? $currentPath === $menuUrl
                        : str_starts_with($currentPath, $menuUrl); ?>
                    <li class="nav-item">
                        <a href="<?= Html::encode($menuUrl) ?>" class="nav-link <?= $isActive ? 'active' : '' ?>">
                            <i class="nav-icon fa-solid <?= $item['icon'] ?>"></i>
                            <p><?= Html::encode($item['label']) ?></p>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>

    <!-- 主内容 -->
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h3 class="mb-0"><?= Html::encode($this->getTitle() !== '' ? $this->getTitle() : '后台管理') ?></h3></div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php foreach (['flash_success', 'flash_error'] as $flashKey): ?>
                    <?php if ($flash->has($flashKey)): ?>
                        <?php $flashType = $flashKey === 'flash_success' ? 'success' : 'danger'; ?>
                        <div class="alert alert-<?= $flashType ?> alert-dismissible" role="alert">
                            <?= Html::encode((string)($flash->get($flashKey)['info'] ?? '')) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div class="card">
                    <div class="card-body">
                        <?= $content ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
