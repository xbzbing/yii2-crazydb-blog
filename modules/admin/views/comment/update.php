<?php
/**
 * @var yii\web\View $this
 * @var app\models\Comment $model
 */

$this->title = '编辑评论';
$this->params['breadcrumbs'][] = ['label' => '评论管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <b><?= $model->nickname ?></b>对<strong>《<?= $model->post->title ?>》</strong>的评论
            </div>
            <div class="box-body comment-update">
                <?= $this->render('_form', [
                    'model' => $model,
                ]) ?>
            </div>
        </div>
    </div>
</div>