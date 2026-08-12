<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台日志查看。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var list<App\Log\Log> $logs
 * @var App\Web\Pager $pager
 * @var string $type
 * @var list<string> $types
 * @var string|null $csrf
 */

$this->setTitle('日志管理 - 后台管理');
?>

<h1>日志管理</h1>

<form class="admin-filter" method="get">
    <select name="type">
        <option value="">全部类型</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= Html::encode($t) ?>"<?= $type === $t ? ' selected' : '' ?>><?= Html::encode($t) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">筛选</button>
</form>

<form method="post" action="<?= Html::encode($urlGenerator->generate('admin/log/clear')) ?>" class="admin-inline" onsubmit="return confirm('清空全部日志？');">
    <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
    <button type="submit">清空日志</button>
</form>

<table class="admin-table">
    <thead>
    <tr>
        <th>ID</th>
        <th>类型</th>
        <th>用户</th>
        <th>结果</th>
        <th>详情</th>
        <th>时间</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= $log->id ?></td>
            <td><?= Html::encode($log->type) ?></td>
            <td><?= (int)$log->uid > 0 ? '#' . (int)$log->uid : '游客' ?></td>
            <td><?= Html::encode($log->result) ?></td>
            <td class="admin-comment-content" title="<?= Html::encode($log->detail) ?>"><?= Html::encode(mb_strimwidth($log->detail, 0, 80, '...', 'utf-8')) ?></td>
            <td><?= date('Y-m-d H:i', (int)$log->create_time) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if ($logs === []): ?>
        <tr><td colspan="6">暂无日志。</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?= $this->render('Pager/pager.php', [
    'pager' => $pager,
    'urlGenerator' => $urlGenerator,
    'route' => 'admin/log/list',
    'pageRoute' => 'admin/log/list-page',
    'routeArgs' => $type !== '' ? ['type' => $type] : [],
]) ?>
