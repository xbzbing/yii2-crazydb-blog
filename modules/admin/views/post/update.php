<?php

/* @var $this yii\web\View */
/* @var $model app\models\Post */

$this->title = '编辑文章';
$this->params['breadcrumbs'][] = ['label' => '所有文章', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = '编辑';
?>
<div class="box post-update">
    <div class="box-header with-border">
        <i class="fa fa-edit"></i>
        <h3 class="box-title">编辑</h3>
        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>