<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 注册页（等价 Yii2 views/site/register.php）。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var bool $closed
 * @var array<string, string> $errors
 * @var array<string, string> $form
 * @var string|null $csrf
 */

$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('注册 - ' . $siteName);
?>

<div class="breadcrumbs">
    <i class="fa-solid fa-location-dot"></i>
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 注册
</div>

<div class="auth-card">
    <h1>注册</h1>
    <?php if ($closed): ?>
        <p>抱歉，本站暂不开放注册。</p>
        <p><a href="<?= Html::encode($urlGenerator->generate('site/login')) ?>">返回登录</a></p>
    <?php else: ?>
        <form class="auth-form" method="post" action="<?= Html::encode($urlGenerator->generate('site/register')) ?>">
            <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
            <?php foreach ($errors as $field => $message): ?>
                <p class="form-error" data-field="<?= Html::encode($field) ?>"><?= Html::encode($message) ?></p>
            <?php endforeach; ?>
            <div class="form-row">
                <label for="reg-username">用户名</label>
                <input type="text" id="reg-username" name="username" value="<?= Html::encode($form['username']) ?>" required placeholder="20 字符以内">
            </div>
            <div class="form-row">
                <label for="reg-nickname">昵称</label>
                <input type="text" id="reg-nickname" name="nickname" value="<?= Html::encode($form['nickname']) ?>" required placeholder="80 字符以内">
            </div>
            <div class="form-row">
                <label for="reg-email">电子邮箱</label>
                <input type="email" id="reg-email" name="email" value="<?= Html::encode($form['email']) ?>" required placeholder="your@email.com">
            </div>
            <div class="form-row">
                <label for="reg-password">密码</label>
                <input type="password" id="reg-password" name="password" required placeholder="8-20 字符">
            </div>
            <div class="form-row">
                <label for="reg-password-repeat">确认密码</label>
                <input type="password" id="reg-password-repeat" name="password_repeat" required placeholder="请再次输入密码">
            </div>
            <div class="form-row">
                <label for="reg-website">个人网站</label>
                <input type="text" id="reg-website" name="website" value="<?= Html::encode($form['website']) ?>" placeholder="https://（可选）">
            </div>
            <div class="form-row">
                <label for="reg-info">个人简介</label>
                <textarea id="reg-info" name="info" rows="3" placeholder="介绍一下自己（可选）"><?= Html::encode($form['info']) ?></textarea>
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
            <div class="form-submit">
                <button type="submit">注册</button>
                <p class="form-link">已有账号？<a href="<?= Html::encode($urlGenerator->generate('site/login')) ?>">直接登录</a></p>
            </div>
        </form>
    <?php endif; ?>
</div>
