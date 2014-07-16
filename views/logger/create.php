<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Logger $model
 */

$this->title = 'Create Logger';
$this->params['breadcrumbs'][] = ['label' => 'Loggers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="logger-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
