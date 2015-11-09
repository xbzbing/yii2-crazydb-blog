<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\components\XUtils;
use app\models\User;
use app\models\ModifyPassword;

/**
 * @var yii\web\View $this
 * @var ModifyPassword $model
 */

$current_user = $model->getUser();

$this->title = '更改密码: ' . ' ' . $current_user->nickname;
$this->params['breadcrumbs'][] = ['label' => $current_user->nickname, 'url' => ['user/show', 'name' => $current_user->nickname]];
$this->params['breadcrumbs'][] = '个人资料';
?>
<div class="user-update content">
    <div class="base-profile">
        <img src="<?= XUtils::getAvatar($current_user->email) ?>" class="img-thumbnail">
        <h3><?= $current_user->username ?></h3>
    </div>
    <?php $form = ActiveForm::begin(); ?>
    <div class="box-body">

        <?= $form->field($model, 'old_password')->passwordInput() ?>

        <?= $form->field($model, 'password')->passwordInput() ?>

        <?= $form->field($model, 'password_repeat')->passwordInput() ?>

    </div>
    <div class="box-footer form-group">
        <?= Html::submitButton('修改', ['class' => 'btn btn-success']) ?>
        <?= Html::a('返回', ['user/show', 'name' => $current_user->nickname], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

