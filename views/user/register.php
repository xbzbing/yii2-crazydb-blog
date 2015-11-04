<?php
use app\models\User;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var ActiveForm $form
 * @var User $model
 * @var yii\web\View $this
 */

$this->params['breadcrumbs'] = ['注册'];
?>
<h1>用户注册</h1>

<div class="form col-md-offset-1 col-md-5">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
    ]); ?>

    <div class="input-group">
        <span class="input-group-addon"><em class="glyphicon glyphicon-user"></em></span>
        <?= $form->field($model,'username')->textInput(array('class'=>'form-control','placeholder'=>'用户名（登录用）','title'=>'用户名'))->label(false); ?>
    </div>
    <div class="input-group">
        <span class="input-group-addon"><em class="glyphicon glyphicon-user"></em></span>
        <?= $form->field($model,'nickname')->textInput(array('class'=>'form-control','placeholder'=>'昵称','title'=>'昵称'))->label(false); ?>
    </div>
    <div class="input-group">
        <span class="input-group-addon"><em class="glyphicon glyphicon-lock"></em></span>
        <?= $form->field($model,'password')->textInput(array('class'=>'form-control','placeholder'=>'密码','title'=>'密码'))->label(false); ?>
    </div>
    <div class="input-group">
        <span class="input-group-addon"><em class="glyphicon glyphicon-lock"></em></span>
        <?= $form->field($model,'password_repeat')->textInput(array('class'=>'form-control','placeholder'=>'确认密码','title'=>'确认密码'))->label(false); ?>
    </div>
    <div class="input-group">
        <span class="input-group-addon"><em class="glyphicon glyphicon-envelope"></em></span>
        <?= $form->field($model,'email')->textInput(array('type'=>'email','size'=>60,'maxlength'=>100,'class'=>'form-control','placeholder'=>'邮箱','title'=>'邮箱'))->label(false); ?>
    </div>
    <div class="input-group">
        <span class="input-group-addon"><em class="glyphicon glyphicon-globe"></em></span>
        <?= $form->field($model,'website')->textInput(array('size'=>60,'maxlength'=>100,'class'=>'form-control','placeholder'=>'网站URL（选填）','title'=>'网站URL（选填）'))->label(false); ?>
    </div>
    <div class="input-group">
        <span class="input-group-addon"><em class="glyphicon glyphicon-qrcode"></em></span>
        <?= $form->field($model,'info')->textarea(array('rows'=>5,'class'=>'form-control','placeholder'=>'个人简介（选填）','title'=>'个人简介（选填）'))->label(false); ?>
    </div>
    <div class="btn-group">
        <?= Html::submitButton($model->isNewRecord ? '注册' : '保存', ['class'=>'btn btn-primary']); ?>
        <a class="btn btn-default" href="<?= Url::to(['site/login']) ?>">返回登录</a>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<div class="alert alert-info col-md-5">
    <p>1、注册后默认用户角色为&lt;Member&gt;，即普通会员，具备管理自己的留言等功能。</p>
    <p>2、普通用户没有后台模块访问权限，&lt;Author&gt;及以上管理角色拥有相应的后台管理权限。</p>
    <p>3、如果需要使用其他特殊模块，需要另外提交申请。</p>
</div>
<?= $form->errorSummary($model,['class'=>'alert alert-danger col-md-5']); ?>

<script>
    $(function(){
        $('input[id^=User_]').tooltip({'placement':'right','container':'body','trigger':'focus'});
    });
</script>