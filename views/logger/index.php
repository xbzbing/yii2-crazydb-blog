<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\LoggerSearch $searchModel
 */

$this->title = 'Loggers';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="logger-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Logger', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'uid',
            'status',
            'optype',
            'info',
            // 'create_time:datetime',
            // 'ip',
            // 'user_agent',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
