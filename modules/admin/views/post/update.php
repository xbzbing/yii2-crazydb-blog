<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Post $model
 */

$this->title = Yii::t('app', 'Update {modelClass}', ['modelClass' => '文章']);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Posts Manage'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="post-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
