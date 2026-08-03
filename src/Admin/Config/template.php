<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 后台站点配置。
 *
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array<string, string> $values
 * @var array<string, array{label: string, type: string}> $fields
 * @var string|null $csrf
 */

$this->setTitle('站点配置 - 后台管理');
?>

<h1>站点配置</h1>

<form class="admin-form" method="post" action="<?= Html::encode($urlGenerator->generate('admin/config')) ?>">
    <input type="hidden" name="_csrf" value="<?= Html::encode((string)$csrf) ?>">
    <?php foreach ($fields as $name => $field): ?>
        <label><?= Html::encode($field['label']) ?>：
            <input type="text" name="<?= Html::encode($name) ?>" value="<?= Html::encode($values[$name] ?? '') ?>">
        </label>
    <?php endforeach; ?>
    <button type="submit">保存</button>
</form>
