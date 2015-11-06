<?php
use yii\helpers\Html;
/* @var $this yii\web\View */
/* @var $model app\models\Nav */
$this->title = '更新导航';
$this->params['breadcrumbs'][] = ['label' => '导航', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = '更新';
?>
<div class="box nav-index">
    <div class="box-header with-border">
        <h3 class="box-title">导航</h3>
        <div class="box-tools pull-right">
            <span class="label label-primary">Label</span>
        </div>
    </div>
    <div class="box-body">
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>