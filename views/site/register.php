<?php
use app\models\User;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\captcha\Captcha;

/**
 * @var ActiveForm $form
 * @var User $model
 * @var yii\web\View $this
 */

$this->params['breadcrumbs'] = ['注册'];
$template = '<div class="input-group"><span class="input-group-addon"><em class="glyphicon glyphicon-{icon}"></em></span>{input}</div>';
$this->registerJs("$('[id^=user-]').tooltip({'placement':'right','container':'body','trigger':'focus'});");

?>
    <div class="container form-title panel-heading site-register">
        <h2>用户注册</h2>
    </div>
<?php $form = ActiveForm::begin([
    'id' => 'login-form',
    'options' => ['class' => 'form-horizontal'],
]); ?>
    <div class="form col-md-offset-1 col-md-5">
        <?= $form->field($model, 'username', ['template' => str_replace('{icon}', 'user', $template)])->textInput(['placeholder' => '用户名（登录用）', 'title' => '用户名']) ?>
        <?= $form->field($model, 'nickname', ['template' => str_replace('{icon}', 'user', $template)])->textInput(['placeholder' => '昵称', 'title' => '昵称']) ?>
        <?= $form->field($model, 'password', ['template' => str_replace('{icon}', 'lock', $template)])->input('password', ['placeholder' => '密码', 'title' => '密码']) ?>
        <?= $form->field($model, 'password_repeat', ['template' => str_replace('{icon}', 'lock', $template)])->input('password', ['placeholder' => '确认密码', 'title' => '确认密码']) ?>
        <?= $form->field($model, 'email', ['template' => str_replace('{icon}', 'envelope', $template)])->input('email', ['placeholder' => '邮箱', 'title' => '邮箱']) ?>
        <?= $form->field($model, 'website', ['template' => str_replace('{icon}', 'globe', $template)])->textInput(['placeholder' => '网站URL（选填）', 'title' => '网站URL（选填）']) ?>
        <?= $form->field($model, 'info', ['template' => str_replace('{icon}', 'qrcode', $template)])->textarea(['rows' => 5, 'placeholder' => '个人简介（选填）', 'title' => '个人简介（选填）']) ?>
        <?= $form->field($model, 'captcha')->widget(Captcha::className(), [
            'template' => '<div class="input-group"><span class="input-group-addon"><em class="glyphicon glyphicon-ok-sign"></em></span>{input}<span class="input-group-addon captcha-cover">{image}</span></div>',
            'options' => ['tabindex' => '3', 'class' => 'form-control'],
            'imageOptions' => ['alt' => '点击换图', 'title' => '点击换图', 'style' => 'cursor:pointer', 'height' => '32']
        ])->label(false)->error(false) ?>
    </div>
    <div class="col-md-offset-1 col-md-5">
        <div class="alert alert-info col-md-12">
            <p>1、注册后默认用户角色为&lt;Member&gt;，即普通会员，具备管理自己的留言等功能。</p>

            <p>2、普通用户没有后台模块访问权限，&lt;Editor&gt;及以上管理角色拥有相应的后台管理权限。</p>

            <p>3、如果需要使用其他特殊模块，需要另外提交申请。</p>
        </div>
        <?= $form->errorSummary($model, ['class' => 'alert alert-danger col-md-12']); ?>
    </div>
    <div class="clearfix"></div>
    <div class="input-group col-md-12" style="text-align: center">
        <?= Html::submitButton('注册', ['class' => 'btn btn-primary']); ?>
        <a class="btn btn-default" href="<?= Url::to(['site/login']) ?>">返回登录</a>
    </div>
<?php ActiveForm::end(); ?>