<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Nav */

$this->title = '编辑导航';
$this->params['breadcrumbs'][] = ['label' => '导航', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-header with-border">
        <i class="fa fa-edit"></i>
        <h3 class="box-title">编辑导航</h3>
        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i
                    class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i
                    class="fa fa-times"></i></button>
        </div>
    </div>
    <div class="box-body">
        <table class="table table-hover">
            <tr>
                <td>名称</td>
                <td><?= $model->name ?></td>
            </tr>
            <tr>
                <td>类型</td>
                <td><?= $model->pid ? $model->navType . ' - ' . $model->parent->name : $model->navType ?></td>
            </tr>
            <tr>
                <td>网址</td>
                <td><?= $model->url ?></td>
            </tr>
            <tr>
                <td>显示顺序</td>
                <td><?= $model->sort_order ?></td>
            </tr>
        </table>
    </div>
    <div class="box-footer">
        <?= Html::a('更新', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('删除', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => '删除操作不可恢复，确定删除？',
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a('返回', ['nav/index'], ['class' => 'btn btn-default']) ?>
    </div>
</div>
