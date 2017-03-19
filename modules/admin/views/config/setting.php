<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use app\models\Option;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var app\modules\admin\models\SettingForm $model
 * @var array $themes
 */
$this->title = '基本设置';
$this->params['breadcrumbs'][] = '系统设置';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">基本设置</h3>

        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
        </div>
    </div>
    <?php
    $form = ActiveForm::begin([
        'id' => 'setting-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "<div>{label}{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
            'labelOptions' => ['class' => ''],
        ],
    ]); ?>
    <div class="box-body">
        <div class="alert alert-success col-lg-12">
            <p>这里的配置将覆盖config/web的配置，比如网站名称，主题设置，管理员邮箱。</p>
        </div>
        <div class="col-md-12 col-lg-5">
            <?= $form->field($model, Option::SITE_NAME, ['inputOptions' => ['class' => 'form-control']]) ?>
            <?= $form->field($model, Option::SITE_IPC, ['inputOptions' => ['class' => 'form-control']]) ?>
            <?= $form->field($model, Option::ADMIN_EMAIL, ['inputOptions' => ['class' => 'form-control']]) ?>
            <?= $form->field($model, Option::THEME, ['inputOptions' => ['class' => 'form-control']])->dropDownList($themes) ?>
            <?= $form->field($model, Option::COPYRIGHT, ['inputOptions' => ['class' => 'form-control']]) ?>
        </div>
        <div class="col-md-12 col-lg-offset-1 col-lg-5">
            <?= $form->field($model, Option::ALLOW_COMMENT, ['inputOptions' => ['class' => 'form-control']])->dropDownList(['open' => '允许评论', 'closed' => '禁止评论']) ?>
            <?= $form->field($model, Option::ALLOW_REGISTER, ['inputOptions' => ['class' => 'form-control']])->dropDownList(['open' => '允许注册', 'closed' => '禁止注册']) ?>
            <?= $form->field($model, Option::SITE_STATUS, ['inputOptions' => ['class' => 'form-control']])->dropDownList(['open' => '正常运行', 'closed' => '维护中，暂时关闭']) ?>
            <?= $form->field($model, Option::CLOSE_SUMMARY, ['inputOptions' => ['class' => 'form-control autogrow']])->textarea(['rows' => 3]) ?>
            <?= $form->field($model, Option::SITE_ANALYZER, ['inputOptions' => ['class' => 'form-control autogrow']])->textarea(['rows' => 3]) ?>

        </div>
        <div class="clearfix"></div>
    </div>
    <div class="box-footer">
        <?= Html::submitButton('保存', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        <?= Html::a('返回管理首页', ['default/index'], ['class' => 'btn btn-default', 'name' => 'login-button']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
