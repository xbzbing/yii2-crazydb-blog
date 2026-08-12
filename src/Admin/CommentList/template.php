<?php

declare(strict_types=1);

use App\Comment\Comment;
use Yiisoft\Html\Html;

/**
 * 后台评论管理。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var list<App\Comment\Comment> $comments
 * @var App\Web\Pager $pager
 * @var string $status
 */

$this->setTitle('评论管理 - 后台管理');
$statusNames = [
    'approved' => '已审核',
    'unapproved' => '未审核',
    'spam' => '垃圾评论',
];
?>

<h1>评论管理</h1>

<form class="admin-filter" method="get">
    <select name="status">
        <option value="">全部状态</option>
        <?php foreach ($statusNames as $value => $label): ?>
            <option value="<?= $value ?>"<?= $status === $value ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">筛选</button>
</form>

<table class="admin-table">
    <thead>
    <tr>
        <th>ID</th>
        <th>文章</th>
        <th>昵称</th>
        <th>内容</th>
        <th>状态</th>
        <th>时间</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($comments as $comment): ?>
        <tr>
            <td><?= (int)$comment->id ?></td>
            <td>#<?= $comment->pid ?></td>
            <td><?= Html::encode($comment->nickname) ?></td>
            <td class="admin-comment-content"><?= Html::encode(mb_strimwidth((string)$comment->content, 0, 60, '...', 'utf-8')) ?></td>
            <td><?= Html::encode((string)($statusNames[$comment->status] ?? $comment->status)) ?></td>
            <td><?= date('Y-m-d H:i', (int)$comment->create_time) ?></td>
            <td>
                <?php if ($comment->status !== Comment::STATUS_APPROVED): ?>
                    <a href="<?= Html::encode($urlGenerator->generate('admin/comment/action', ['action' => 'approve', 'id' => $comment->id])) ?>">通过</a>
                <?php endif; ?>
                <a href="<?= Html::encode($urlGenerator->generate('admin/comment/action', ['action' => 'delete', 'id' => $comment->id])) ?>" onclick="return confirm('确定删除该评论？');">删除</a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($comments === []): ?>
        <tr><td colspan="7">暂无评论。</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?= $this->render('Pager/pager.php', [
    'pager' => $pager,
    'urlGenerator' => $urlGenerator,
    'route' => 'admin/comment/list',
    'pageRoute' => 'admin/comment/list-page',
    'routeArgs' => $status !== '' ? ['status' => $status] : [],
]) ?>
