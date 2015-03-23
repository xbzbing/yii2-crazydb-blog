<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\CommentSearch $searchModel
 */

$this->title = Yii::t('app', 'Comments Manage');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="comment-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'commentType',
            'content:html',
            'commentStatus',
            'post.title',
            'create_time:datetime',
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
