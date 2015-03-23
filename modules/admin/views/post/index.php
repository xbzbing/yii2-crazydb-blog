<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Lookup;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\PostSearch $searchModel
 */

use app\components\CMSUtils;
use app\models\Post;

$this->title = Yii::t('app', 'Posts Manage');
$this->params['breadcrumbs'][] = $this->title;
$categories = CMSUtils::getAllCategories();
?>
<div class="post-index">
    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'title',
            [
                'attribute' => 'status',
                'value' => 'postStatus',
                'filter' => Html::activeDropDownList($searchModel, 'status', ['' => '全部'] + Post::getAvailableStatus(), ['class' => 'form-control']),
            ],
            [
                'attribute' => 'cid',
                'value' => 'postCategory',
                'filter' => Html::activeDropDownList($searchModel, 'cid', ['' => '全部'] + $categories, ['class' => 'form-control']),
            ],
            [
                'attribute' => 'post_time',
                'value' => 'post_time',
                'format' => ['datetime','php:Y-m-d H:i:s'],
                'filter' => false,
            ],
            [
                'attribute' => 'view_count',
                'value' => 'view_count',
                'filter' => false,
            ],
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
