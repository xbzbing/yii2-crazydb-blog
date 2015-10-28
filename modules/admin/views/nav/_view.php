<?php
use app\models\Nav;
use yii\helpers\Url;

/* @var Nav $model */
?>
<tr>
    <td><?= $model->id ?></td>
    <?= $model->pid ? '<td></td><td>' . $model->name . '</td>' : '<td colspan="2">' . $model->name . '</td>' ?>
    <td><?= $model->url?></td>
    <td><?= $model->order ?></td>
    <td>
        <a href="<?= Url::to(['view', 'id' => $model->id]) ?>" title="查看"><i class="fa fa-eye"> </i></a>
        <a href="<?= Url::to(['update', 'id' => $model->id]) ?>" title="编辑"><i class="fa fa-edit"> </i></a>
        <a href="<?= Url::to(['delete', 'id' => $model->id]) ?>" title="删除" data-confirm="删除操作将删除菜单自身及其子菜单，操作无法恢复，确定删除？"
           data-method="post"><i class="fa  fa-trash-o"> </i></a>
    </td>
</tr>