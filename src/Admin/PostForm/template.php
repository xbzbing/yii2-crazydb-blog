<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台文章编辑表单。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Post\Post $post
 * @var bool $isNew
 * @var array<int, string> $categories
 * @var array<string, string> $errors
 * @var string|null $csrf
 */

$this->setTitle(($isNew ? '新建文章' : '编辑文章') . ' - 后台管理');
$formAction = $isNew
    ? $urlGenerator->generate('admin/post/create')
    : $urlGenerator->generate('admin/post/update', ['id' => $post->id]);
$statusNames = ['published' => '已发布', 'hidden' => '隐藏', 'draft' => '草稿', 'deleted' => '已删除'];
$formats = ['html' => 'HTML（老文章）', 'markdown' => 'Markdown'];
?>

<h1><?= $isNew ? '新建文章' : '编辑文章' ?></h1>

<?php foreach ($errors as $field => $message): ?>
    <p class="form-error"><?= Html::encode($field) ?>：<?= Html::encode($message) ?></p>
<?php endforeach; ?>

<form class="admin-form" method="post" action="<?= Html::encode($formAction) ?>">
    <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
    <label>标题：<input type="text" name="title" value="<?= Html::encode($post->title) ?>" required></label>
    <label>别名（URL 友好名，可选）：<input type="text" name="alias" value="<?= Html::encode($post->alias) ?>" placeholder="如 hello-world"></label>
    <label>分类：
        <select name="cid">
            <option value="0">未分类</option>
            <?php foreach ($categories as $cid => $name): ?>
                <option value="<?= $cid ?>"<?= (int)$post->cid === (int)$cid ? ' selected' : '' ?>><?= Html::encode($name) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>状态：
        <select name="status">
            <?php foreach ($statusNames as $value => $label): ?>
                <option value="<?= $value ?>"<?= $post->status === $value ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>格式：
        <select name="format">
            <?php foreach ($formats as $value => $label): ?>
                <option value="<?= $value ?>"<?= $post->format === $value ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>标签（逗号分隔）：<input type="text" name="tags" value="<?= Html::encode($post->tags) ?>"></label>
    <label>发布时间（时间戳，留空为当前）：<input type="number" name="post_time" value="<?= $post->post_time ?: '' ?>" placeholder="<?= time() ?>"></label>
    <label class="admin-check"><input type="checkbox" name="is_top" value="1"<?= $post->is_top ? ' checked' : '' ?>> 置顶</label>
    <label>摘要（可选）：<textarea name="excerpt" rows="3"><?= Html::encode((string)$post->excerpt) ?></textarea></label>
    <label>正文：
        <textarea name="content" rows="16" class="admin-content"><?= Html::encode((string)$post->content) ?></textarea>
    </label>
    <label>访问密码（可选，隐藏文章用）：<input type="text" name="password" value="<?= Html::encode((string)$post->password) ?>"></label>
    <button type="submit">保存</button>
    <a href="<?= Html::encode($urlGenerator->generate('admin/post/list')) ?>">返回列表</a>
</form>
