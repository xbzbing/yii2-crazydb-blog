<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台分类表单。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Category\Category $category
 * @var bool $isNew
 * @var array<string, string> $errors
 * @var string|null $csrf
 */

$this->setTitle(($isNew ? '新建分类' : '编辑分类') . ' - 后台管理');
$formAction = $isNew
    ? $urlGenerator->generate('admin/category/create')
    : $urlGenerator->generate('admin/category/update', ['id' => $category->id]);
?>

<h1><?= $isNew ? '新建分类' : '编辑分类' ?></h1>

<?php foreach ($errors as $field => $message): ?>
    <p class="form-error"><?= Html::encode($field) ?>：<?= Html::encode($message) ?></p>
<?php endforeach; ?>

<form class="admin-form" method="post" action="<?= Html::encode($formAction) ?>">
    <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
    <label>名称：<input type="text" name="name" value="<?= Html::encode($category->name) ?>" required></label>
    <label>别名（URL 友好名，可选）：<input type="text" name="alias" value="<?= Html::encode($category->alias) ?>"></label>
    <label>描述（可选）：<textarea name="desc" rows="3"><?= Html::encode((string)$category->desc) ?></textarea></label>
    <label>关键词（SEO，可选）：<input type="text" name="keywords" value="<?= Html::encode($category->keywords) ?>"></label>
    <label>排序（大在前）：<input type="number" name="sort_order" value="<?= $category->sort_order ?>"></label>
    <button type="submit">保存</button>
    <a href="<?= Html::encode($urlGenerator->generate('admin/category/list')) ?>">返回列表</a>
</form>
