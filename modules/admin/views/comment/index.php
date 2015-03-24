<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Comment;
use app\models\search\CommentSearch;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var CommentSearch $searchModel
 */

$this->title = Yii::t('app', 'Comments Manage');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
            </div>
            <div class="box-body post-index">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'attribute' => 'author',
                        'value' => function (Comment $model, $key, $index, $column){
                            if($model->uid>0){
                                return Html::a($model->author,['user/view','id'=>$model->uid]);
                            }else
                                return $model->author;
                        },
                        'format' => 'raw'
                    ],
                    'content:html',
                    [
                        'attribute' => 'type',
                        'value' => 'commentType',
                        'filter' => Html::activeDropDownList($searchModel, 'type', ['' => '全部'] + Comment::getAvailableType(), ['class' => 'form-control']),
                    ],
                    [
                        'attribute' => 'status',
                        'value' => 'commentStatus',
                        'filter' => Html::activeDropDownList($searchModel, 'status', ['' => '全部'] + Comment::getAvailableStatus(), ['class' => 'form-control']),
                    ],
                    [
                        'attribute' => 'pid',
                        'value' => 'post.title',
                        'filter' => false,
                        'label' => '所属文章',
                        'enableSorting' => true
                    ],
                    'email',
                    [
                        'attribute' => 'create_time',
                        'value' => 'create_time',
                        'format' => ['datetime','php:Y-m-d H:i:s'],
                        'filter' => false,
                    ],
                    ['class' => 'yii\grid\ActionColumn'],

                ],
            ]); ?>
            </div>
        </div>
    </div>
</div>