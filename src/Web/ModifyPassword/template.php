<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 修改密码页 v2。
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

<div class="breadcrumbs">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    <span style="margin:0 4px;opacity:.4">/</span>
    修改密码
</div>

<div class="auth-card" style="max-width:480px;">
    <h1>修改密码</h1>
    <p class="auth-card-sub">修改后所有设备的登录状态将失效</p>
    <?php foreach ($errors as $field => $message): ?>
        <p class="form-error" data-field="<?= Html::encode($field) ?>"><?= Html::encode($message) ?></p>
    <?php endforeach; ?>
    <form class="auth-form" method="post" action="<?= Html::encode($urlGenerator->generate('user/modify-password')) ?>">
        <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
        <div class="form-row">
            <label>旧密码</label>
            <input type="password" name="old_password" required autofocus placeholder="请输入当前密码">
        </div>
        <div class="form-row">
            <label>新密码</label>
            <input type="password" name="password" required placeholder="8-20 字符">
        </div>
        <div class="form-row">
            <label>确认新密码</label>
            <input type="password" name="password_repeat" required placeholder="请再次输入新密码">
        </div>
        <div class="form-submit">
            <button type="submit">确认修改</button>
        </div>
    </form>
</div>
