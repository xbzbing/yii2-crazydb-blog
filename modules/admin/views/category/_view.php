<?php
use yii\helpers\Url;
use yii\helpers\Html;
use app\models\Category;

/* @var Category $model */
?>
<tr>
    <td><?= $model->id ?></td>
    <?= $model->pid ? '<td></td><td>' . $model->name . '</td>' : '<td colspan="2">' . $model->name . '</td>' ?>
    <td><?= Html::a($model->alias, ['/category/alias', 'alias' => $model->alias], ['target' => '_blank']) ?></td>
    <td><?= $model->sort_order ?></td>
    <td>
        <a href="<?= Url::to(['view', 'id' => $model->id]) ?>" title="查看"><i class="fa fa-eye"> </i></a>
        <a href="<?= Url::to(['update', 'id' => $model->id]) ?>" title="编辑"><i class="fa fa-edit"> </i></a>
        <a href="<?= Url::to(['delete', 'id' => $model->id]) ?>" title="删除" data-confirm="删除后子分类将升级，操作无法恢复，确定删除？"
           data-method="post"><i class="fa  fa-trash-o"> </i></a>
    </td>
</tr>