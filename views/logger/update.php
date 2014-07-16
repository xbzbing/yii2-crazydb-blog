<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Logger $model
 */

$this->title = 'Update Logger: ' . ' ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Loggers', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="logger-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
