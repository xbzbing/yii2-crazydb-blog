<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 登录页（等价 Yii2 views/site/login.php）。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var string $username
 * @var bool $locked
 * @var int $lockRemaining
 * @var string $redirect
 * @var string|null $csrf
 */

$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('登录 - ' . $siteName);
?>

<div class="breadcrumbs">
    <i class="fa-solid fa-location-dot"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 登录
</div>

<div class="auth-card">
    <h1>登录</h1>
    <?php if ($locked): ?>
        <p class="form-error">登录失败次数过多，请 <?= (int)ceil($lockRemaining / 60) ?> 分钟后再试。</p>
    <?php else: ?>
        <form class="auth-form" method="post" action="<?= Html::encode($urlGenerator->generate('site/login')) ?>">
            <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
            <?php if ($redirect !== ''): ?>
                <input type="hidden" name="redirect" value="<?= Html::encode($redirect) ?>">
            <?php endif; ?>
            <div class="form-row">
                <label for="login-username">用户名</label>
                <input type="text" id="login-username" name="username" value="<?= Html::encode($username) ?>" required autofocus placeholder="请输入用户名">
            </div>
            <div class="form-row">
                <label for="login-password">密码</label>
                <input type="password" id="login-password" name="password" required placeholder="请输入密码">
            </div>
            <div class="form-row">
                <label>验证码</label>
                <div class="captcha-row">
                    <img src="<?= Html::encode($urlGenerator->generate('tool/captcha')) ?>" alt="验证码" class="auth-captcha" onclick="this.src+='?'+Date.now()" title="点击刷新">
                    <div class="captcha-input">
                        <input type="text" name="captcha" required placeholder="请输入验证码">
                    </div>
                </div>
            </div>
            <label class="remember-row"><input type="checkbox" name="rememberMe" value="1"> 记住我（30 天）</label>
            <div class="form-submit">
                <button type="submit">登录</button>
                <?php if (($siteConfig['allow_register'] ?? '') === \App\Option\Option::STATUS_OPEN): ?>
                    <p class="form-link">还没有账号？<a href="<?= Html::encode($urlGenerator->generate('site/register')) ?>">立即注册</a></p>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>
