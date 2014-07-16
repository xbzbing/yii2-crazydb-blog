<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\Comment $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="comment-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'pid')->textInput(['maxlength' => 20]) ?>

    <?= $form->field($model, 'author')->textInput(['maxlength' => 80]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => 100]) ?>

    <?= $form->field($model, 'ip')->textInput(['maxlength' => 100]) ?>

    <?= $form->field($model, 'create_time')->textInput() ?>

    <?= $form->field($model, 'content')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'ext')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'uid')->textInput(['maxlength' => 20]) ?>

    <?= $form->field($model, 'replyto')->textInput(['maxlength' => 20]) ?>

    <?= $form->field($model, 'update_time')->textInput() ?>

    <?= $form->field($model, 'type')->dropDownList([ 'replyTo' => 'ReplyTo', 'reply' => 'Reply', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'status')->dropDownList([ 'unapproved' => 'Unapproved', 'approved' => 'Approved', 'spam' => 'Spam', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'url')->textInput(['maxlength' => 255]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
