<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Comment;

/**
 * @var yii\web\View $this
 * @var Comment $model
 */

$this->title = '查看评论';
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Comments Manage'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <b><?=$model->author?></b>对<strong>《<?=$model->post->title?>》</strong>的评论
            </div>
            <div class="box-body comment-view">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'id',
                        'content:html',
                        'commentStatus',
                        [
                            'attribute' => 'type',
                            'value' => $model->type == Comment::TYPE_REPLYTO?Html::a($model->commentType,['comment/view','id'=>$model->id]):$model->commentType,
                            'format' => 'raw'

                        ],
                        'create_time:datetime',
                        'update_time:datetime',
                        'author',
                        'email',
                        'url',
                        'ip',
                        'user_agent'
                    ],
                ]) ?>
            </div>
        </div>
        <div class="form-group">
            <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    'method' => 'post',
                ],
            ]) ?>
            <?= Html::a(Yii::t('app', 'Back'), ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
</div>