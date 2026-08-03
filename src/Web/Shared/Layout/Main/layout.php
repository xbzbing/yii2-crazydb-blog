<?php


use App\Web\Shared\Layout\Main\MainAsset;
use Yiisoft\Html\Html;

/**
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

$assetManager->register(MainAsset::class);

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

$this->beginPage()
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($applicationParams->locale) ?>">
<head>
    <meta charset="<?= Html::encode($applicationParams->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= $aliases->get('@baseUrl/favicon.svg') ?>" type="image/svg+xml">
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

<header class="site-header">
    <div class="container">
        <div class="site-logo">
            <a href="<?= $urlGenerator->generate('site/index') ?>"><?= Html::encode($siteName) ?></a>
        </div>
        <nav class="site-nav">
            <ul>
                <?php foreach ($navTree as $item): ?>
                    <li>
                        <a href="<?= Html::encode((string)($item['url'] ?? '#')) ?>"><?= Html::encode((string)($item['label'])) ?></a>
                        <?php if (!empty($item['items'])): ?>
                            <ul class="sub-nav">
                                <?php foreach ($item['items'] as $sub): ?>
                                    <li><a href="<?= Html::encode((string)($sub['url'] ?? '#')) ?>"><?= Html::encode((string)($sub['label'])) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <li><a href="<?= $urlGenerator->generate('feed/rss') ?>">RSS</a></li>
                <?php $currentUser = $authService->currentUser(); ?>
                <?php if ($currentUser !== null): ?>
                    <li><a href="<?= Html::encode($urlGenerator->generate('user/show', ['name' => $currentUser->nickname])) ?>"><?= Html::encode($currentUser->nickname) ?></a></li>
                    <li>
                        <form method="post" action="<?= $urlGenerator->generate('site/logout') ?>" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                            <button type="submit" class="link-button">退出</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li><a href="<?= $urlGenerator->generate('site/login') ?>">登录</a></li>
                    <li><a href="<?= $urlGenerator->generate('site/register') ?>">注册</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<div class="site-body container">
    <?php foreach (['flash_success', 'flash_error'] as $flashKey): ?>
        <?php if ($flash->has($flashKey)): ?>
            <?php $flashType = $flashKey === 'flash_success' ? 'flash-success' : 'flash-error'; ?>
            <div class="flash <?= $flashType ?>"><?= Html::encode((string)($flash->get($flashKey)['info'] ?? '')) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="site-main<?= $showSidebar ? '' : ' site-main-full' ?>">
        <?= $content ?>
    </div>
    <?php if ($showSidebar): ?>
        <aside class="site-sidebar">
            <?= $this->render('Sidebar/sidebar.php', [
                'siteConfig' => $siteConfig,
                'categorySummary' => $this->getParameter('categorySummary', []),
                'tags' => $this->getParameter('sidebarTags', []),
                'recentComments' => $this->getParameter('sidebarComments', []),
            ]) ?>
        </aside>
    <?php endif; ?>
</div>

<footer class="site-footer">
    <div class="container">
        <span><?= Html::encode($siteName) ?></span>
        <span><?= Html::encode((string)($siteConfig['copyright'] ?? 'Copyright © 2013-' . date('Y'))) ?></span>
        <span><?= Html::encode((string)($siteConfig['site_icp'] ?? '')) ?></span>
        <?php if (!empty($siteConfig['site_analyzer'])): ?>
            <div class="site-analyzer"><?= $siteConfig['site_analyzer'] ?></div>
        <?php endif; ?>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
