<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\components\XUtils;
use app\models\User;

/**
 * @var yii\web\View $this
 * @var User $model
 */

$this->title = '用户资料: ' . ' ' . $model->nickname;
$this->params['breadcrumbs'][] = ['label' => $model->nickname, 'url' => ['user/show', 'name' => $model->nickname]];
$this->params['breadcrumbs'][] = '个人资料';
?>
<div class="user-update content">
    <div class="base-profile">
        <img src="<?= XUtils::getAvatar($model->email) ?>" class="img-thumbnail">
        <h3><?= $model->username ?></h3>
    </div>
    <?php $form = ActiveForm::begin(); ?>
    <div class="box-body">

        <?= $form->field($model, 'nickname')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'email')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'website')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'info')->textarea(['rows' => 5]) ?>

    </div>
    <div class="box-footer form-group">
        <?= Html::submitButton('保存', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?= Html::a('返回', ['user/show', 'name' => $model->nickname], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

