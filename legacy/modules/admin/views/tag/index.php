<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\TagSearch $searchModel
 */

$this->title = '标签管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tag-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            'name',
            'pid',
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
