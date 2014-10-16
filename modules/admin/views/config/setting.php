<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var app\modules\admin\models\SettingForm $model
 * @var array $themes
 */

$form = ActiveForm::begin([
    'id' => 'setting-form',
    'options' => ['class' => 'form-horizontal'],
    'fieldConfig' => [
        'template' => "<div class=\"form-group\">{label}{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
        'labelOptions' => ['class' => ''],
    ],
]); ?>
<div class="col-md-5">
    <?= $form->field($model, 'site_name',['inputOptions'=>['class'=>'form-control']]) ?>
    <?= $form->field($model, 'site_icp',['inputOptions'=>['class'=>'form-control']]) ?>
    <?= $form->field($model, 'admin_email',['inputOptions'=>['class'=>'form-control']]) ?>
    <?= $form->field($model, 'allow_register',['inputOptions'=>['class'=>'form-control']])->dropDownList(['open'=>'允许注册','closed'=>'不允许注册']) ?>
    <?= $form->field($model, 'theme',['inputOptions'=>['class'=>'form-control']])->dropDownList($themes) ?>
    <?= $form->field($model, 'copyright',['inputOptions'=>['class'=>'form-control']]) ?>
    <?= $form->field($model, 'site_analyzer',['inputOptions'=>['class'=>'form-control']]) ?>
</div>
<div class="col-md-offset-1 col-md-5">
    <?= $form->field($model, 'allow_comment',['inputOptions'=>['class'=>'form-control']])->dropDownList(['open'=>'开启评论功能','closed'=>'关闭评论功能']) ?>
    <?= $form->field($model, 'need_approve',['inputOptions'=>['class'=>'form-control']])->dropDownList(['open'=>'需要审核','closed'=>'不需要审核']) ?>
    <?= $form->field($model, 'send_mail_on_comment',['inputOptions'=>['class'=>'form-control']])->dropDownList(['open'=>'发送邮件','closed'=>'不发送邮件']) ?>
    <?= $form->field($model, 'site_name',['inputOptions'=>['class'=>'form-control']])->dropDownList(['open'=>'正常运行','closed'=>'维护中，暂时关闭']) ?>
    <?= $form->field($model, 'site_name',['inputOptions'=>['class'=>'form-control']]) ?>
    <?= $form->field($model, 'closed_summary',['inputOptions'=>['class'=>'form-control autogrow']])->textarea(['rows'=>6]) ?>
</div>
<div class="form-group">
    <div class="col-lg-offset-1 col-lg-11">
        <?= Html::submitButton(Yii::t('app','Save'), ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        <?= Html::a(Yii::t('app','Back'),['config/setting'], ['class' => 'btn btn-default', 'name' => 'login-button']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>