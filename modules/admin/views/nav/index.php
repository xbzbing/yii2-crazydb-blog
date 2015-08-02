<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\NavSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '导航';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box nav-index">
    <div class="box-header with-border">
        <h3 class="box-title">导航</h3>
    </div>
    <div class="box-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'id',
                'pid',
                'name',
                'url:url',
                'order',
                ['class' => 'yii\grid\ActionColumn'],
            ],
        ]); ?>
    </div>
    <div class="box-footer">
        <?= Html::a('添加', ['create'], ['class' => 'btn btn-success']) ?>
    </div>
</div>