<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\search\UserSearch $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="user-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'nickname') ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'password') ?>

    <?= $form->field($model, 'email') ?>

    <?php // echo $form->field($model, 'url') ?>

    <?php // echo $form->field($model, 'acl') ?>

    <?php // echo $form->field($model, 'regip') ?>

    <?php // echo $form->field($model, 'regtime') ?>

    <?php // echo $form->field($model, 'salt') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'info') ?>

    <?php // echo $form->field($model, 'ext') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
