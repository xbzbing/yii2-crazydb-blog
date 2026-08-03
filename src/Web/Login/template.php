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
 * @var string|null $csrf
 */

$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('登录 - ' . $siteName);
?>

<div class="auth-card">
    <h1>登录</h1>
    <?php if ($locked): ?>
        <p class="form-error">登录失败次数过多，请 <?= (int)ceil($lockRemaining / 60) ?> 分钟后再试。</p>
    <?php else: ?>
        <form class="auth-form" method="post" action="<?= Html::encode($urlGenerator->generate('site/login')) ?>">
            <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
            <label>用户名：<input type="text" name="username" value="<?= Html::encode($username) ?>" required autofocus></label>
            <label>密码：<input type="password" name="password" required></label>
            <label>验证码：
                <img src="<?= Html::encode($urlGenerator->generate('tool/captcha')) ?>" alt="验证码" class="auth-captcha">
                <input type="text" name="captcha" required>
            </label>
            <label class="auth-remember"><input type="checkbox" name="rememberMe" value="1"> 记住我（30 天）</label>
            <button type="submit">登录</button>
        </form>
        <p>还没有账号？<a href="<?= Html::encode($urlGenerator->generate('site/register')) ?>">立即注册</a></p>
    <?php endif; ?>
</div>
