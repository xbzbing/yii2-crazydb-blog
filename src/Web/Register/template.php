<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 注册页 v2：居中卡片 + 垂直表单布局。
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
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    <span style="margin:0 4px;opacity:.4">/</span>
    注册
</div>

<div class="auth-card" style="max-width:480px;">
    <h1>创建账号</h1>
    <p class="auth-card-sub">注册后即可参与评论和互动</p>

    <?php if ($closed): ?>
        <div class="form-error" style="text-align:center;">
            抱歉，本站暂不开放注册。
        </div>
        <div class="form-submit" style="margin-top:20px;">
            <a href="<?= Html::encode($urlGenerator->generate('site/login')) ?>"
               style="display:block;text-align:center;padding:10px;background:var(--accent);color:#fff;border-radius:8px;font-weight:600;">返回登录</a>
        </div>
    <?php else: ?>
        <form class="auth-form" method="post" action="<?= Html::encode($urlGenerator->generate('site/register')) ?>">
            <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
            <?php foreach ($errors as $field => $message): ?>
                <p class="form-error" data-field="<?= Html::encode($field) ?>"><?= Html::encode($message) ?></p>
            <?php endforeach; ?>

            <div class="form-row">
                <label for="reg-username">用户名</label>
                <input type="text" id="reg-username" name="username"
                       value="<?= Html::encode($form['username']) ?>"
                       required placeholder="20 字符以内"
                       autocomplete="username">
            </div>
            <div class="form-row">
                <label for="reg-nickname">昵称</label>
                <input type="text" id="reg-nickname" name="nickname"
                       value="<?= Html::encode($form['nickname']) ?>"
                       required placeholder="80 字符以内">
            </div>
            <div class="form-row">
                <label for="reg-email">电子邮箱</label>
                <input type="email" id="reg-email" name="email"
                       value="<?= Html::encode($form['email']) ?>"
                       required placeholder="your@email.com"
                       autocomplete="email">
            </div>
            <div class="form-row">
                <label for="reg-password">密码</label>
                <input type="password" id="reg-password" name="password"
                       required placeholder="8-20 字符"
                       autocomplete="new-password">
            </div>
            <div class="form-row">
                <label for="reg-password-repeat">确认密码</label>
                <input type="password" id="reg-password-repeat" name="password_repeat"
                       required placeholder="请再次输入密码"
                       autocomplete="new-password">
            </div>
            <div class="form-row">
                <label for="reg-website">个人网站</label>
                <input type="url" id="reg-website" name="website"
                       value="<?= Html::encode($form['website']) ?>"
                       placeholder="https://（可选）">
            </div>
            <div class="form-row">
                <label for="reg-info">个人简介</label>
                <textarea id="reg-info" name="info" rows="3"
                          placeholder="介绍一下自己（可选）"
                          style="resize:vertical;min-height:72px;"><?= Html::encode($form['info']) ?></textarea>
            </div>
            <div class="form-row">
                <label>验证码</label>
                <div class="captcha-row">
                    <img src="<?= Html::encode($urlGenerator->generate('tool/captcha')) ?>"
                         alt="验证码" class="auth-captcha"
                         onclick="this.src+='?'+Date.now()" title="点击刷新">
                    <div class="captcha-input">
                        <input type="text" name="captcha" required placeholder="请输入验证码"
                               autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit">注 册</button>
                <p class="form-link">已有账号？<a href="<?= Html::encode($urlGenerator->generate('site/login')) ?>">直接登录</a></p>
            </div>
        </form>
    <?php endif; ?>
</div>
