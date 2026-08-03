<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台导航管理（树形）。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<int, list<App\Nav\Nav>> $childrenByPid
 * @var list<int> $parentIds
 * @var string|null $csrf
 */

$this->setTitle('导航管理 - 后台管理');
?>

<h1>导航管理</h1>
<p><a href="<?= Html::encode($urlGenerator->generate('admin/nav/create')) ?>" class="btn">新建导航</a></p>

<table class="admin-table">
    <thead>
    <tr>
        <th>ID</th>
        <th>名称</th>
        <th>URL / 路由</th>
        <th>类型</th>
        <th>排序</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($childrenByPid[0] ?? [] as $node): ?>
        <tr>
            <td><?= $node->id ?></td>
            <td><strong><?= Html::encode($node->name) ?></strong></td>
            <td><?= Html::encode($node->url) ?></td>
            <td><?= $node->route === 1 ? '系统路由' : '自定义链接' ?></td>
            <td><?= $node->sort_order ?></td>
            <td>
                <a href="<?= Html::encode($urlGenerator->generate('admin/nav/update', ['id' => $node->id])) ?>">编辑</a>
                <form method="post" action="<?= Html::encode($urlGenerator->generate('admin/nav/delete', ['id' => $node->id])) ?>" class="admin-inline" onsubmit="return confirm('删除该导航将连带删除其子导航，确定？');">
                    <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                    <button type="submit">删除</button>
                </form>
            </td>
        </tr>
        <?php foreach ($childrenByPid[(int)$node->id] ?? [] as $child): ?>
            <tr>
                <td><?= $child->id ?></td>
                <td style="padding-left:32px">└ <?= Html::encode($child->name) ?></td>
                <td><?= Html::encode($child->url) ?></td>
                <td><?= $child->route === 1 ? '系统路由' : '自定义链接' ?></td>
                <td><?= $child->sort_order ?></td>
                <td>
                    <a href="<?= Html::encode($urlGenerator->generate('admin/nav/update', ['id' => $child->id])) ?>">编辑</a>
                    <form method="post" action="<?= Html::encode($urlGenerator->generate('admin/nav/delete', ['id' => $child->id])) ?>" class="admin-inline" onsubmit="return confirm('确定删除该导航？');">
                        <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                        <button type="submit">删除</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <?php if (empty($childrenByPid[0])): ?>
        <tr><td colspan="6">暂无导航。前台 header 仅显示固定链接（归档/RSS/登录）。</td></tr>
    <?php endif; ?>
    </tbody>
</table>
