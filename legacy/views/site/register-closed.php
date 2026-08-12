<?php
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/**
 * @var ActiveForm $form
 */
$this->params['breadcrumbs'] = ['注册'];
?>
<div class="container form-title panel-heading site-register">
    <h2>用户注册</h2>
</div>
<div class="col-md-12">
    <div class="alert alert-info">
        <p>抱歉，本站暂时关闭了注册。</p>
        <br>
        <a href="<?= Url::home() ?>" title="首页" class="btn btn-success">首页</a>
        <a href="<?= Url::to('site/login') ?>" title="返回登录" class="btn btn-default">返回登录</a>
    </div>
</div>