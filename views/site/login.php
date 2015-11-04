<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\captcha\Captcha;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var app\models\LoginForm $model
 */
$this->title = '登录';
$this->params['breadcrumbs'] = [$this->title];
?>
<div class="container site-login">
    <h1>用户登录</h1>
</div>
<div class="form col-md-offset-2 col-md-4">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
    ]); ?>
    <?= $form->field($model, 'username', ['template' => '<div class="input-group"><span class="input-group-addon"><em class="glyphicon glyphicon-user"></em></span>{input}</div>'])->textInput(['class' => 'form-control', 'placeholder' => '用户名', 'tabindex' => '1']); ?>
    <?= $form->field($model, 'password', ['template' => '<div class="input-group"><span class="input-group-addon"><em class="glyphicon glyphicon-lock"></em></span>{input}</div>'])->passwordInput(['class' => 'form-control', 'placeholder' => '密码', 'tabindex' => '2'])->label(false) ?>
    <?= $form->field($model, 'captcha')->widget(Captcha::className(), [
        'template' => '<div class="input-group"><span class="input-group-addon"><em class="glyphicon glyphicon-ok-sign"></em></span>{input}<span class="input-group-addon captcha-cover">{image}</span></div>',
        'options' => ['tabindex' => '3', 'class' => 'form-control'],
        'imageOptions' => ['alt' => '点击换图', 'title' => '点击换图', 'style' => 'cursor:pointer', 'height' => '32']
    ])->label(false) ?>
    <?= $form->field($model, 'rememberMe')->checkbox(['tabIndex' => 4]) ?>
    <div>
        <?= Html::submitButton('登录', ['class' => 'btn btn-primary', 'tabIndex' => 5, 'name' => 'login-button']) ?>
        <?= Html::a('注册', ['user/register']); ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
<?= $form->errorSummary($model, ['class' => 'alert alert-danger col-md-offset-1 col-md-3']); ?>

