<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Comment;

/**
 * @var yii\web\View $this
 * @var Comment $model
 */

$this->title = '查看评论';
$this->params['breadcrumbs'][] = ['label' => '评论管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <b><?= $model->nickname ?></b>对<strong>《<?= $model->post->title ?>》</strong>的评论
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
                            'value' => $model->isReply() ? Html::a($model->commentType, ['comment/view', 'id' => $model->id]) : $model->commentType,
                            'format' => 'raw'

                        ],
                        'create_time:datetime',
                        'update_time:datetime',
                        'nickname',
                        'email',
                        'url',
                        'ip',
                        'user_agent'
                    ],
                ]) ?>
            </div>
        </div>
        <div class="form-group">
            <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('删除', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'Are you sure you want to delete this item?',
                    'method' => 'post',
                ],
            ]) ?>
            <?= Html::a('返回', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>
</div>