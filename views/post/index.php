<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\PostSearch $searchModel
 */

$this->title = 'Posts';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="post-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Post', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            'cid',
            'author_id',
            'author_name',
            'type',
            // 'title',
            // 'alias',
            // 'excerpt:ntext',
            // 'content:ntext',
            // 'cover',
            // 'password',
            // 'status',
            // 'create_time:datetime',
            // 'post_time:datetime',
            // 'update_time:datetime',
            // 'tags',
            // 'comment_count',
            // 'view_count',
            // 'options',
            // 'ext_info:ntext',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
