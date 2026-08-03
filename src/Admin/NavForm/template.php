<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台导航表单。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var App\Nav\Nav $nav
 * @var bool $isNew
 * @var list<App\Nav\Nav> $parents
 * @var array<string, string> $errors
 * @var string|null $csrf
 */

$this->setTitle(($isNew ? '新建导航' : '编辑导航') . ' - 后台管理');
$formAction = $isNew
    ? $urlGenerator->generate('admin/nav/create')
    : $urlGenerator->generate('admin/nav/update', ['id' => $nav->id]);
?>

<h1><?= $isNew ? '新建导航' : '编辑导航' ?></h1>

<?php foreach ($errors as $field => $message): ?>
    <p class="form-error"><?= Html::encode($field) ?>：<?= Html::encode($message) ?></p>
<?php endforeach; ?>

<form class="admin-form" method="post" action="<?= Html::encode($formAction) ?>">
    <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
    <label>名称：<input type="text" name="name" value="<?= Html::encode($nav->name) ?>" required></label>
    <label>URL 或路由名：
        <input type="text" name="url" value="<?= Html::encode($nav->url) ?>" required
               placeholder="自定义链接填 http(s)://…；系统路由填路由名（如 post/list）">
    </label>
    <label>类型：
        <select name="route">
            <option value="0"<?= $nav->route !== 1 ? ' selected' : '' ?>>自定义链接</option>
            <option value="1"<?= $nav->route === 1 ? ' selected' : '' ?>>系统路由</option>
        </select>
    </label>
    <label>父导航（可选，仅支持两级）：
        <select name="pid">
            <option value="0">顶级</option>
            <?php foreach ($parents as $parent): ?>
                <?php if ((int)$parent->id === (int)$nav->id) {
                    continue;
                } ?>
                <option value="<?= $parent->id ?>"<?= (int)$nav->pid === (int)$parent->id ? ' selected' : '' ?>><?= Html::encode($parent->name) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>排序（大在前）：<input type="number" name="sort_order" value="<?= (int)$nav->sort_order ?>"></label>
    <button type="submit">保存</button>
    <a href="<?= Html::encode($urlGenerator->generate('admin/nav/list')) ?>">返回列表</a>
</form>
