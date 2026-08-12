<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var Yiisoft\View\WebView $this
 * @var \App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 */

$this->setTitle('404 - 页面不存在');
?>

<div class="text-center">
    <h1>404</h1>

    <p>
        页面
        <strong><?= Html::encode($currentRoute->getUri()?->getPath() ?? '未知') ?></strong>
        不存在。
    </p>

    <p>
        您访问的页面可能已被删除或移动，请检查网址是否正确。
    </p>

    <p>
        <a href="<?= $urlGenerator->generate('site/index') ?>">返回首页</a>
    </p>
</div>
