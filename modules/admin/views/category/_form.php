<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Category;

/**
 * @var yii\web\View $this
 * @var app\models\Category $model
 * @var yii\widgets\ActiveForm $form
 * @var array $category_array
 */
?>

<div class="category-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($model, 'desc')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'display')->dropDownList(Category::getAvailableDisplay()) ?>

    <?= $form->field($model, 'parent')->dropDownList($category_array) ?>

    <?= $form->field($model, 'sort_order')->textInput() ?>

    <?= $form->field($model, 'alias')->textInput(['maxlength' => 100]) ?>

    <?= $form->field($model, 'seo_title')->textInput(['maxlength' => 100]) ?>
    <?= $form->field($model, 'seo_keywords')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'seo_description')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
