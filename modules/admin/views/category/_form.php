<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Category;

use crazydb\ueditor\UEditor;

/**
 * @var yii\web\View $this
 * @var app\models\Category $model
 * @var yii\widgets\ActiveForm $form
 * @var array $category_array
 */
?>
<div class="box category-form">
    <div class="box-header with-border">
        <h3 class="box-title"></h3>

        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i
                    class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i
                    class="fa fa-times"></i></button>
        </div>
    </div>
    <?php $form = ActiveForm::begin(); ?>
    <div class="box-body">
        <?= $form->field($model, 'name')->textInput(['maxlength' => 255, 'tabIndex' => 1]) ?>
        <div class="row">
            <div class="col-md-12 col-lg-4">
                <?= $form->field($model, 'alias')->textInput(['maxlength' => 100]) ?>
            </div>
            <div class="col-md-12 col-lg-4">
                <?= $form->field($model, 'sort_order')->input('number') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-lg-4">
                <?= $form->field($model, 'parent')->dropDownList($category_array) ?>
            </div>
            <div class="col-md-12 col-lg-4">
                <?= $form->field($model, 'display')->dropDownList(Category::getAvailableDisplay()) ?>
            </div>
        </div>
        <?= $form->field($model, 'keywords')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'desc')->textarea(['rows' => 6])->widget(UEditor::className(), [
            'config' => [
                'toolbars' => [
                    ['source', 'link', 'bold', 'italic', 'underline', 'forecolor', 'superscript', 'insertimage', 'spechars', 'blockquote']
                ],
                'initialFrameHeight' => '150',
            ]
        ]) ?>
    </div>
    <div class="box-footer form-group">
        <?= Html::submitButton($model->isNewRecord ? '创建' : '保存', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?= Html::a('返回', Yii::$app->request->referrer, ['class' => 'btn btn-default']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>