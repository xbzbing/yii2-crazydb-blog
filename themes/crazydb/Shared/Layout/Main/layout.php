<?php

declare(strict_types=1);

use App\Theme\Crazydb\CrazydbAsset;
use Yiisoft\Html\Html;

/**
 * Crazydb 主题布局（还原线上 crazydb.com：蓝底 header + 圆形 logo + navbar + 两栏 + 深色 footer）。
 *
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Aliases\Aliases $aliases
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var string $content
 * @var string|null $csrf
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var Yiisoft\Session\Flash\FlashInterface $flash
 * @var App\User\AuthService $authService
 */

$assetManager->register(CrazydbAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

/** @var Yiisoft\Assets\AssetBundle $themeAsset 主题资源发布 URL（logo 等图片相对此路径） */
$themeAsset = $assetManager->getBundle(CrazydbAsset::class);

/** @var array<string, string|null> $siteConfig */
$siteConfig = $this->getParameter('siteConfig', []);
/** @var array<int, array{label: string, url: string, items: array<int, array{label: string, url: string}>}> $navTree */
$navTree = $this->getParameter('navTree', []);
$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$pageTitle = $this->getTitle();
if ($pageTitle === '') {
    $pageTitle = (string)($siteConfig['seo_title'] ?? $siteName);
}
$seoKeywords = (string)($this->getParameter('seo_keywords', ''));
$seoDescription = (string)($this->getParameter('seo_description', ''));
$showSidebar = (bool)$this->getParameter('showSidebar', true);
$currentUser = $authService->currentUser();

$this->beginPage()
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.ico') ?>" type="image/x-icon">
    <title><?= Html::encode($pageTitle) ?></title>
    <?php if ($seoKeywords !== ''): ?>
        <meta name="keywords" content="<?= Html::encode($seoKeywords) ?>">
    <?php endif; ?>
    <?php if ($seoDescription !== ''): ?>
        <meta name="description" content="<?= Html::encode($seoDescription) ?>">
    <?php endif; ?>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="site-container">

    <header class="index-header">
        <nav class="container" role="navigation">
            <div class="row">
                <div class="col-md-4 index-logo">
                    <img class="rounded-circle" src="<?= Html::encode((string)$themeAsset->baseUrl . '/images/site-logo.jpg') ?>" alt="<?= Html::encode($siteName) ?>">
                </div>
                <div class="col-md-8">
                    <nav class="navbar navbar-expand-md navbar-light bg-light index-navbar with-shadow">
                        <div class="container-fluid">
                            <a class="navbar-brand" href="<?= $urlGenerator->generate('site/index') ?>"><?= Html::encode($siteName) ?></a>
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#site-navbar" aria-controls="site-navbar" aria-expanded="false" aria-label="切换导航">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            <div class="collapse navbar-collapse" id="site-navbar">
                                <ul class="navbar-nav me-auto">
                                    <?php foreach ($navTree as $item): ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="<?= Html::encode((string)($item['url'] ?? '#')) ?>"><?= Html::encode((string)($item['label'])) ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <ul class="navbar-nav">
                                    <?php if ($currentUser === null): ?>
                                        <li class="nav-item dropdown">
                                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">用户</a>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="<?= $urlGenerator->generate('site/login') ?>">登录</a></li>
                                                <?php if (($siteConfig['allow_register'] ?? '') === \App\Option\Option::STATUS_OPEN): ?>
                                                    <li><a class="dropdown-item" href="<?= $urlGenerator->generate('site/register') ?>">注册</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </li>
                                    <?php else: ?>
                                        <li class="nav-item dropdown">
                                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false"><?= Html::encode($currentUser->nickname) ?></a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="<?= Html::encode($urlGenerator->generate('user/profile', ['name' => $currentUser->nickname])) ?>">个人资料</a></li>
                                                <li><a class="dropdown-item" href="<?= Html::encode($urlGenerator->generate('user/profile-edit')) ?>">修改资料</a></li>
                                                <li><a class="dropdown-item" href="<?= $urlGenerator->generate('user/modify-password') ?>">修改密码</a></li>
                                                <?php if ($currentUser->isAdmin()): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item" href="<?= $urlGenerator->generate('admin/index') ?>">管理后台</a></li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="post" action="<?= $urlGenerator->generate('site/logout') ?>" class="m-0" id="logout-form">
                                                        <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                                                        <button type="submit" class="dropdown-item">退出</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </nav>
    </header>

    <div class="main">
        <div class="container">
            <div class="row">
                <div class="col-md-<?= $showSidebar ? 9 : 12 ?> post-list list-group no-padding with-shadow">
                    <?php foreach (['flash_success', 'flash_error'] as $flashKey): ?>
                        <?php if ($flash->has($flashKey)): ?>
                            <?php $flashType = $flashKey === 'flash_success' ? 'success' : 'danger'; ?>
                            <div class="alert alert-<?= $flashType ?> alert-dismissible fade show flash-toast" role="alert"<?= $flashKey === 'flash_success' ? ' data-flash-toast' : '' ?>>
                                <?= Html::encode((string)($flash->get($flashKey)['info'] ?? '')) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <script>
                        document.querySelectorAll('[data-flash-toast]').forEach(function (el) {
                            setTimeout(function () {
                                el.style.transition = 'opacity .6s ease';
                                el.style.opacity = '0';
                                setTimeout(function () { el.remove(); }, 600);
                            }, 3500);
                        });
                    </script>
                    <?= $content ?>
                </div>
                <?php if ($showSidebar): ?>
                    <aside class="col-md-3 sidebar" id="right-sidebar">
                        <?= $this->render('Sidebar/sidebar.php', [
                            'siteConfig' => $siteConfig,
                            'categorySummary' => $this->getParameter('categorySummary', []),
                            'tags' => $this->getParameter('sidebarTags', []),
                            'recentComments' => $this->getParameter('sidebarComments', []),
                            'markdownRenderer' => $this->getParameter('markdownRenderer', null),
                        ]) ?>
                    </aside>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer id="footer">
        <div class="container more-information">
            <div class="row">
                <div class="col-md-3 friend-link">
                </div>
                <div class="col-md-8">
                    <h3>WHAT'S THE NEXT?</h3>
                </div>
            </div>
        </div>
        <div id="copyright">
            <span><?= Html::encode($siteName) ?></span>
            <span>Copyright &copy; 2013-<?= date('Y') ?></span>
            <span><?= Html::encode((string)($siteConfig['site_icp'] ?? '')) ?></span>
            <?php if (!empty($siteConfig['site_analyzer'])): ?>
                <div class="scriptAnalyzer"><?= $siteConfig['site_analyzer'] ?></div>
            <?php endif; ?>
        </div>
    </footer>

</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
