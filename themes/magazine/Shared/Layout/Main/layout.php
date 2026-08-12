<?php

declare(strict_types=1);

use App\Theme\Magazine\MagazineAsset;
use Yiisoft\Html\Html;

/**
 * 墨刊主题布局（印刷刊物质感）。
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

$assetManager->register(MagazineAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.ico') ?>" type="image/x-icon">
    <link rel="stylesheet" href="/static/fonts/noto-serif-sc.css">
    <title><?= Html::encode($pageTitle) ?></title>
    <?php if ($seoKeywords !== ''): ?>
        <meta name="keywords" content="<?= Html::encode($seoKeywords) ?>">
    <?php endif; ?>
    <?php if ($seoDescription !== ''): ?>
        <meta name="description" content="<?= Html::encode($seoDescription) ?>">
    <?php endif; ?>
    <?php $this->head() ?>
</head>
<body class="magazine-body">
<?php $this->beginBody() ?>

<header class="magazine-header">
    <div class="magazine-container">
        <div class="magazine-masthead">
            <a href="<?= $urlGenerator->generate('site/index') ?>" class="magazine-logo"><?= Html::encode($siteName) ?></a>
            <p class="magazine-tagline">印刷刊物质感 × 数字阅读体验</p>
        </div>
        <nav class="magazine-nav">
            <?php foreach ($navTree as $item): ?>
                <a href="<?= Html::encode((string)($item['url'] ?? '#')) ?>"><?= Html::encode((string)($item['label'])) ?></a>
                <?php if (!empty($item['items'])): ?>
                    <?php foreach ($item['items'] as $sub): ?>
                        <a class="magazine-nav-sub" href="<?= Html::encode((string)($sub['url'] ?? '#')) ?>"><?= Html::encode((string)($sub['label'])) ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="<?= $urlGenerator->generate('post/archives') ?>">归档</a>
            <a href="<?= $urlGenerator->generate('feed/rss') ?>">RSS</a>
            <?php if ($currentUser !== null): ?>
                <a href="<?= Html::encode($urlGenerator->generate('user/show', ['name' => $currentUser->nickname])) ?>"><?= Html::encode($currentUser->nickname) ?></a>
                <form method="post" action="<?= $urlGenerator->generate('site/logout') ?>" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                    <button type="submit" class="link-button">退出</button>
                </form>
            <?php else: ?>
                <a href="<?= $urlGenerator->generate('site/login') ?>">登录</a>
                <?php if (($siteConfig['allow_register'] ?? '') === \App\Option\Option::STATUS_OPEN): ?>
                    <a href="<?= $urlGenerator->generate('site/register') ?>">注册</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="magazine-container magazine-layout">
    <?php foreach (['flash_success', 'flash_error'] as $flashKey): ?>
        <?php if ($flash->has($flashKey)): ?>
            <?php $flashType = $flashKey === 'flash_success' ? 'flash-success' : 'flash-error'; ?>
            <div class="flash flash-toast <?= $flashType ?>"<?= $flashKey === 'flash_success' ? ' data-flash-toast' : '' ?>><?= Html::encode((string)($flash->get($flashKey)['info'] ?? '')) ?></div>
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
    <main class="magazine-main">
        <?= $content ?>
    </main>
    <?php if ($showSidebar): ?>
        <aside class="magazine-sidebar">
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

<footer class="magazine-footer">
    <div class="magazine-container">
        <span><?= Html::encode($siteName) ?></span>
        <span><?= Html::encode((string)($siteConfig['copyright'] ?? 'Copyright © 2013-' . date('Y'))) ?></span>
        <span><?= Html::encode((string)($siteConfig['site_icp'] ?? '')) ?></span>
        <?php if (!empty($siteConfig['site_analyzer'])): ?>
            <div class="magazine-analyzer"><?= $siteConfig['site_analyzer'] ?></div>
        <?php endif; ?>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
