<?php

declare(strict_types=1);

use App\User\User;
use Yiisoft\Html\Html;

/**
 * 后台用户管理。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var list<App\User\User> $users
 * @var App\Web\Pager $pager
 * @var string|null $csrf
 */

$this->setTitle('用户管理 - 后台管理');
$statusNames = [
    User::STATUS_NORMAL => '正常',
    User::STATUS_INACTIVE => '未激活',
    User::STATUS_BANED => '已禁用',
    User::STATUS_DELETED => '已删除',
];
$roleNames = [
    User::ROLE_MEMBER => '会员',
    User::ROLE_EDITOR => '编辑',
    User::ROLE_ADMIN => '管理员',
];
?>

<h1>用户管理</h1>

<table class="admin-table">
    <thead>
    <tr>
        <th>ID</th>
        <th>用户名</th>
        <th>昵称</th>
        <th>邮箱</th>
        <th>角色</th>
        <th>状态</th>
        <th>注册时间</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user->id ?></td>
            <td><?= Html::encode($user->username) ?></td>
            <td><?= Html::encode($user->nickname) ?></td>
            <td><?= Html::encode($user->email) ?></td>
            <td><?= Html::encode($roleNames[$user->role] ?? $user->role) ?></td>
            <td><?= Html::encode($statusNames[$user->status] ?? $user->status) ?></td>
            <td><?= date('Y-m-d', (int)$user->register_time) ?></td>
            <td>
                <?php if (!$user->isWebmaster()): ?>
                    <?php if ($user->status !== User::STATUS_BANED): ?>
                        <form method="post" action="<?= Html::encode($urlGenerator->generate('admin/user/action', ['action' => 'ban', 'id' => $user->id])) ?>" class="admin-inline" onsubmit="return confirm('禁用用户「<?= Html::encode($user->nickname) ?>」？');">
                            <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                            <button type="submit">禁用</button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="<?= Html::encode($urlGenerator->generate('admin/user/action', ['action' => 'unban', 'id' => $user->id])) ?>" class="admin-inline">
                            <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                            <button type="submit">启用</button>
                        </form>
                    <?php endif; ?>
                    <details class="admin-inline-details">
                        <summary>编辑</summary>
                        <form method="post" action="<?= Html::encode($urlGenerator->generate('admin/user/action', ['action' => 'update', 'id' => $user->id])) ?>" class="admin-inline">
                            <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                            <input type="text" name="nickname" value="<?= Html::encode($user->nickname) ?>" required maxlength="80" placeholder="昵称">
                            <select name="role">
                                <?php foreach ($roleNames as $roleValue => $roleName): ?>
                                    <option value="<?= $roleValue ?>" <?= (int)$user->role === $roleValue ? 'selected' : '' ?>><?= Html::encode($roleName) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">保存</button>
                        </form>
                    </details>
                <?php else: ?>
                    <span>（站长）</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($users === []): ?>
        <tr><td colspan="8">暂无用户。</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?= $this->render('Pager/pager.php', [
    'pager' => $pager,
    'urlGenerator' => $urlGenerator,
    'route' => 'admin/user/list',
    'pageRoute' => 'admin/user/list-page',
    'routeArgs' => [],
]) ?>
