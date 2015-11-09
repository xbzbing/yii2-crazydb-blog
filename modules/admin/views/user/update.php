<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\User;

/**
 * @var yii\web\View $this
 * @var User $model
 */

$this->title = '编辑用户:'.$model->username;
$this->params['breadcrumbs'][] = ['label' => '用户管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->username, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = '更新';
?>
<div class="box user-update">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Html::encode($model->username) ?></h3>

        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i
                    class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i
                    class="fa fa-times"></i></button>
        </div>
    </div>
    <?php $form = ActiveForm::begin(); ?>
    <div class="box-body">

        <?= $form->field($model, 'username')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'nickname')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'email')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'website')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'role')->dropDownList(User::getAvailableRole()) ?>

        <?= $form->field($model, 'status')->dropDownList(User::getAvailableStatus()) ?>

        <?= $form->field($model, 'info')->textarea(['rows' => 5]) ?>

    </div>
    <div class="box-footer form-group">
        <?= Html::submitButton('保存', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?= Html::a('返回', ['user/index'], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

