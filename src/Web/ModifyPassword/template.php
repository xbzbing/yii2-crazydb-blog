<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 修改密码页。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var array<string, string> $errors
 * @var string|null $csrf
 */

$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('修改密码 - ' . $siteName);
?>

<nav class="breadcrumb">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 用户
    » 修改密码
</nav>

<div class="auth-card">
    <h1>修改密码</h1>
    <?php foreach ($errors as $field => $message): ?>
        <p class="form-error" data-field="<?= Html::encode($field) ?>"><?= Html::encode($message) ?></p>
    <?php endforeach; ?>
    <form class="auth-form" method="post" action="<?= Html::encode($urlGenerator->generate('user/modify-password')) ?>">
        <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
        <label>旧密码：<input type="password" name="old_password" required autofocus></label>
        <label>新密码（8-20 字符）：<input type="password" name="password" required></label>
        <label>确认新密码：<input type="password" name="password_repeat" required></label>
        <button type="submit">确认修改</button>
    </form>
    <p>修改成功后需使用新密码重新登录（所有设备的登录状态与记住我均失效）。</p>
</div>
