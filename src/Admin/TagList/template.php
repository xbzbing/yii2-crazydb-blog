<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台标签管理。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var list<array{name: string, totalCount: int}> $tags
 * @var string|null $csrf
 */

$this->setTitle('标签管理 - 后台管理');
?>

<h1>标签管理</h1>

<table class="admin-table">
    <thead>
    <tr>
        <th>标签名</th>
        <th>文章数</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($tags as $tag): ?>
        <tr>
            <td><a href="<?= Html::encode($urlGenerator->generate('admin/post/list', [], ['tag' => $tag['name']])) ?>"><?= Html::encode($tag['name']) ?></a></td>
            <td><?= (int)$tag['totalCount'] ?></td>
            <td>
                <a href="<?= Html::encode($urlGenerator->generate('tag/show', ['name' => $tag['name']])) ?>" target="_blank">查看</a>
                <?php if ((int)$tag['totalCount'] > 0): ?>
                    <button type="button" onclick="alert('该标签关联 <?= (int)$tag['totalCount'] ?> 篇文章，无法删除；请先在文章中移除该标签。')">删除</button>
                <?php else: ?>
                    <form method="post" action="<?= Html::encode($urlGenerator->generate('admin/tag/delete', ['name' => $tag['name']])) ?>" class="admin-inline" onsubmit="return confirm('确认删除标签「<?= Html::encode($tag['name']) ?>」？');">
                        <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
                        <button type="submit">删除</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($tags === []): ?>
        <tr><td colspan="3">暂无标签。文章发布时填写标签会自动建立。</td></tr>
    <?php endif; ?>
    </tbody>
</table>
