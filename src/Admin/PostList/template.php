<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台文章列表。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var list<App\Post\Post> $posts
 * @var App\Web\Pager $pager
 * @var string $status
 */

$this->setTitle('文章管理 - 后台管理');
$statusNames = [
    'published' => '已发布',
    'hidden' => '隐藏',
    'draft' => '草稿',
    'deleted' => '已删除',
];
?>

<h1>文章管理</h1>
<p><a href="<?= Html::encode($urlGenerator->generate('admin/post/create')) ?>" class="btn">新建文章</a></p>

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
        <th>标题</th>
        <th>状态</th>
        <th>格式</th>
        <th>发布时间</th>
        <th>评论</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($posts as $post): ?>
        <tr>
            <td><?= $post->id ?></td>
            <td><a href="<?= Html::encode($urlGenerator->generate('admin/post/update', ['id' => $post->id])) ?>"><?= Html::encode($post->title) ?></a></td>
            <td><?= Html::encode($statusNames[$post->status] ?? $post->status) ?></td>
            <td><?= Html::encode((string)$post->format) ?></td>
            <td><?= date('Y-m-d', (int)$post->post_time) ?></td>
            <td><?= (int)$post->comment_count ?></td>
            <td>
                <a href="<?= Html::encode($urlGenerator->generate('post/show', ['alias' => $post->alias])) ?>" target="_blank">查看</a>
                <a href="<?= Html::encode($urlGenerator->generate('admin/post/update', ['id' => $post->id])) ?>">编辑</a>
                <a href="<?= Html::encode($urlGenerator->generate('admin/post/delete', ['id' => $post->id])) ?>" onclick="return confirm('确定删除该文章？');">删除</a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($posts === []): ?>
        <tr><td colspan="7">暂无文章。</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<?= $this->render('Pager/pager.php', [
    'pager' => $pager,
    'urlGenerator' => $urlGenerator,
    'route' => 'admin/post/list',
    'pageRoute' => 'admin/post/list-page',
    'routeArgs' => $status !== '' ? ['status' => $status] : [],
]) ?>
