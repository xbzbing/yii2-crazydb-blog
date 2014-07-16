<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\Logger $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="logger-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'uid')->textInput(['maxlength' => 20]) ?>

    <?= $form->field($model, 'create_time')->textInput() ?>

    <?= $form->field($model, 'ip')->textInput(['maxlength' => 15]) ?>

    <?= $form->field($model, 'status')->textInput(['maxlength' => 40]) ?>

    <?= $form->field($model, 'optype')->textInput(['maxlength' => 40]) ?>

    <?= $form->field($model, 'info')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($model, 'user_agent')->textInput(['maxlength' => 255]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
