<?php

declare(strict_types=1);

use App\Common\XUtils;
use Yiisoft\Html\Html;

/**
 * 修改个人资料页（等价 Yii2 views/user/update.php）。
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

<nav class="breadcrumb">
    <a href="<?= $urlGenerator->generate('site/index') ?>">首页</a>
    » 用户
    » 修改个人资料
</nav>

<div class="auth-card user-update">
    <h1>修改个人资料</h1>
    <div class="base-profile">
        <img src="<?= Html::encode(XUtils::getAvatar($aliases, (string)$user->email, 48)) ?>" width="48" height="48" alt="">
        <h3><?= Html::encode($user->username) ?></h3>
    </div>
    <?php foreach ($errors as $field => $message): ?>
        <p class="form-error" data-field="<?= Html::encode($field) ?>"><?= Html::encode($message) ?></p>
    <?php endforeach; ?>
    <form class="auth-form" method="post" action="<?= Html::encode($urlGenerator->generate('user/profile-edit')) ?>">
        <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
        <label>昵称：<input type="text" name="nickname" value="<?= Html::encode($user->nickname) ?>" required maxlength="80" autofocus></label>
        <label>电子邮箱：<input type="email" name="email" value="<?= Html::encode($user->email) ?>" required maxlength="100"></label>
        <label>个人网站：<input type="url" name="website" value="<?= Html::encode((string)($user->website ?? '')) ?>" maxlength="100" placeholder="https://...（选填）"></label>
        <label>个人简介：<textarea name="info" rows="5" maxlength="1000" placeholder="介绍一下自己吧（选填）"><?= Html::encode((string)($user->info ?? '')) ?></textarea></label>
        <button type="submit">保存</button>
    </form>
    <p class="auth-tip">修改成功后返回个人主页；用户名与角色不可修改。</p>
</div>
