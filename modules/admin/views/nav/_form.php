<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Nav;

/* @var $this yii\web\View */
/* @var $model app\models\Nav */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="nav-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'url')->textInput(['maxlength' => true]) ?>
    <div class="row">
        <div class="col-md-12 col-lg-4">
            <?= $form->field($model, 'route')->dropDownList(['否', '是']) ?>
        </div>
        <div class="col-md-12 col-lg-4">
            <?= $form->field($model, 'pid')->dropDownList(['0' => '顶级分类'] + Nav::getParentNav()) ?>
        </div>
    </div>

    <?= $form->field($model, 'sort_order')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('保存', ['class' => 'btn btn-success']) ?>
        <?= Html::a('返回', 'index', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>