<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台布局（轻量管理界面）。
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

$assetManager->register(\App\Web\Shared\Layout\Main\MainAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$currentUser = $authService->currentUser();
$menu = [
    ['label' => '仪表盘', 'route' => 'admin/index'],
    ['label' => '文章管理', 'route' => 'admin/post/list'],
    ['label' => '评论管理', 'route' => 'admin/comment/list'],
    ['label' => '分类管理', 'route' => 'admin/category/list'],
    ['label' => '站点配置', 'route' => 'admin/config'],
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
<body class="admin-body">
<?php $this->beginBody() ?>

<header class="admin-header">
    <div class="container">
        <a href="<?= $urlGenerator->generate('admin/index') ?>" class="admin-logo">后台管理</a>
        <nav class="admin-user">
            <?php if ($currentUser !== null): ?>
                <span><?= Html::encode($currentUser->nickname) ?></span>
                <a href="<?= $urlGenerator->generate('site/index') ?>">返回前台</a>
                <a href="<?= $urlGenerator->generate('site/logout') ?>">退出</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="admin-body container">
    <aside class="admin-sidebar">
        <ul>
            <?php foreach ($menu as $item): ?>
                <li><a href="<?= Html::encode($urlGenerator->generate($item['route'])) ?>"><?= Html::encode($item['label']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </aside>
    <main class="admin-main">
        <?php foreach (['flash_success', 'flash_error'] as $flashKey): ?>
            <?php if ($flash->has($flashKey)): ?>
                <?php $flashType = $flashKey === 'flash_success' ? 'flash-success' : 'flash-error'; ?>
                <div class="flash <?= $flashType ?>"><?= Html::encode((string)($flash->get($flashKey)['info'] ?? '')) ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
