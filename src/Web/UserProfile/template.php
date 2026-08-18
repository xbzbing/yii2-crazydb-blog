<?php

declare(strict_types=1);

use App\Common\XUtils;
use Yiisoft\Html\Html;

/**
 * 修改个人资料页 v2。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string|null> $siteConfig
 * @var array<string, mixed> $navTree
 * @var bool $showSidebar
 * @var array<string, string> $errors
 * @var string|null $csrf
 * @var App\User\User $user
 * @var Yiisoft\Aliases\Aliases $aliases
 */

$this->setParameter('showSidebar', $showSidebar);
$this->setParameter('siteConfig', $siteConfig);
$this->setParameter('navTree', $navTree);

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$this->setTitle('修改个人资料 - ' . $siteName);
?>

<div class="breadcrumbs">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    <span style="margin:0 4px;opacity:.4">/</span>
    修改个人资料
</div>

<div class="auth-card user-update" style="max-width:540px;">
    <div class="base-profile">
        <img src="<?= Html::encode(XUtils::getAvatar($aliases, (string)$user->email, 48)) ?>" width="48" height="48" alt="">
        <div>
            <h3 style="margin:0;font-size:16px;"><?= Html::encode($user->username) ?></h3>
            <p style="margin:2px 0 0;font-size:13px;color:var(--muted);"><?= Html::encode($user->getUserRole()) ?></p>
        </div>
    </div>

    <?php foreach ($errors as $field => $message): ?>
        <p class="form-error" data-field="<?= Html::encode($field) ?>"><?= Html::encode($message) ?></p>
    <?php endforeach; ?>

    <form class="auth-form" method="post" action="<?= Html::encode($urlGenerator->generate('user/profile-edit')) ?>">
        <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
        <div class="form-row">
            <label>昵称</label>
            <input type="text" name="nickname" value="<?= Html::encode($user->nickname) ?>"
                   required maxlength="80" autofocus placeholder="显示在文章作者处">
        </div>
        <div class="form-row">
            <label>电子邮箱</label>
            <input type="email" name="email" value="<?= Html::encode($user->email) ?>"
                   required maxlength="100" placeholder="your@email.com">
        </div>
        <div class="form-row">
            <label>个人网站</label>
            <input type="url" name="website"
                   value="<?= Html::encode((string)($user->website ?? '')) ?>"
                   maxlength="100" placeholder="https://（选填）">
        </div>
        <div class="form-row">
            <label>个人简介</label>
            <textarea name="info" rows="4" maxlength="1000"
                      placeholder="介绍一下自己吧（选填）"
                      style="resize:vertical;min-height:72px;"><?= Html::encode((string)($user->info ?? '')) ?></textarea>
        </div>
        <div class="form-submit">
            <button type="submit">保存修改</button>
        </div>
    </form>
</div>
