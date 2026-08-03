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
            <label>用户名（最多 20 字符）：<input type="text" name="username" value="<?= Html::encode($form['username']) ?>" required></label>
            <label>昵称（最多 80 字符）：<input type="text" name="nickname" value="<?= Html::encode($form['nickname']) ?>" required></label>
            <label>电子邮箱：<input type="email" name="email" value="<?= Html::encode($form['email']) ?>" required></label>
            <label>密码（8-20 字符）：<input type="password" name="password" required></label>
            <label>确认密码：<input type="password" name="password_repeat" required></label>
            <label>个人网站（可选）：<input type="text" name="website" value="<?= Html::encode($form['website']) ?>" placeholder="http:// 或 https://"></label>
            <label>个人简介（可选）：<textarea name="info" rows="3"><?= Html::encode($form['info']) ?></textarea></label>
            <label>验证码：
                <img src="<?= Html::encode($urlGenerator->generate('tool/captcha')) ?>" alt="验证码" class="auth-captcha">
                <input type="text" name="captcha" required>
            </label>
            <button type="submit">注册</button>
        </form>
        <p>已有账号？<a href="<?= Html::encode($urlGenerator->generate('site/login')) ?>">直接登录</a></p>
    <?php endif; ?>
</div>
