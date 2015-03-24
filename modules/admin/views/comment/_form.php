<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

use app\models\Comment;

use crazydb\ueditor\UEditor;

/**
 * @var yii\web\View $this
 * @var app\models\Comment $model
 * @var yii\widgets\ActiveForm $form
 */
?>
<div class="comment-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'content')->textarea(['rows' => 6])->widget(UEditor::className(),[
        'config'=>[
            'toolbars'=>[
                ['source','link','bold','italic','underline','forecolor','superscript','insertimage','spechars','blockquote']
            ],
            'initialFrameHeight'=>'150',
        ]
    ]) ?>

    <?= $form->field($model, 'author')->textInput(['maxlength' => 128]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => 128]) ?>

    <?= $form->field($model, 'url')->textInput(['maxlength' => 255]) ?>
	
	<?= $form->field($model, 'status')->dropDownList(Comment::getAvailableStatus()) ?>

    <?= $form->field($model, 'type')->dropDownList(Comment::getAvailableType()) ?>

    <?= $form->field($model, 'pid')->textInput(['maxlength' => '5']) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Back'), ['index'], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
