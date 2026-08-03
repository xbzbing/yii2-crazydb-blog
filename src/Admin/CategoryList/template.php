<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台分类管理。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var list<App\Category\Category> $categories
 */

$this->setTitle('分类管理 - 后台管理');
?>

<h1>分类管理</h1>
<p><a href="<?= Html::encode($urlGenerator->generate('admin/category/create')) ?>" class="btn">新建分类</a></p>

<table class="admin-table">
    <thead>
    <tr>
        <th>ID</th>
        <th>名称</th>
        <th>别名</th>
        <th>描述</th>
        <th>排序</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($categories as $category): ?>
        <tr>
            <td><?= $category->id ?></td>
            <td><?= Html::encode($category->name) ?></td>
            <td><?= Html::encode($category->alias) ?></td>
            <td><?= Html::encode((string)$category->desc) ?></td>
            <td><?= $category->sort_order ?></td>
            <td>
                <a href="<?= Html::encode($urlGenerator->generate('admin/category/update', ['id' => $category->id])) ?>">编辑</a>
                <a href="<?= Html::encode($urlGenerator->generate('admin/category/delete', ['id' => $category->id])) ?>" onclick="return confirm('确定删除该分类？');">删除</a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($categories === []): ?>
        <tr><td colspan="6">暂无分类。</td></tr>
    <?php endif; ?>
    </tbody>
</table>
