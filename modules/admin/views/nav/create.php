<?php
use yii\helpers\Html;
/* @var $this yii\web\View */
/* @var $model app\models\Nav */
$this->title = '添加导航';
$this->params['breadcrumbs'][] = ['label' => '前台导航', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box nav-index">
    <div class="box-header with-border">
        <h3 class="box-title">添加导航</h3>
    </div>
    <div class="box-body">
        <?= $this->render('_form', [
            'model' => $model,
        ]) ?>
    </div>
</div>