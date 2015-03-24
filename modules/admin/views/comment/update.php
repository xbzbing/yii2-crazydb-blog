<?php
/**
 * @var yii\web\View $this
 * @var app\models\Comment $model
 */

$this->title = Yii::t('app', 'Update {modelClass}', ['modelClass' => '评论']);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Comments Manage'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <b><?=$model->author?></b>对<strong>《<?=$model->post->title?>》</strong>的评论
            </div>
            <div class="box-body comment-update">
            <?= $this->render('_form', [
                'model' => $model,
            ]) ?>
            </div>
        </div>
    </div>
</div>