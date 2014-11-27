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

	<h3>基本信息</h3>
    <?= $form->field($model, 'name')->textInput(['maxlength' => 255, 'tabIndex'=>1]) ?>

	<?= $form->field($model, 'display')->dropDownList(Category::getAvailableDisplay()) ?>

    <?= $form->field($model, 'desc')->textarea(['rows' => 6]) ?>


    <?= $form->field($model, 'parent')->dropDownList($category_array) ?>

    <?= $form->field($model, 'sort_order')->textInput() ?>

    <?= $form->field($model, 'alias')->textInput(['maxlength' => 100]) ?>

	<h3>SEO 设置</h3>
    <?= $form->field($model, 'seo_title')->textInput(['maxlength' => 100]) ?>
    <?= $form->field($model, 'seo_keywords')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'seo_description')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? '创建' : '保存', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    <?= Html::a('返回',Yii::$app->request->referrer,['class'=>'btn btn-default'])?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
