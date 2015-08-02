<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var app\modules\admin\models\SeoForm $model
 */
?>
<div class="col-md-7">
<?php
$form = ActiveForm::begin([
    'id' => 'setting-form',
    'options' => ['class' => 'form-horizontal'],
    'fieldConfig' => [
        'template' => "<div>{label}{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
        'labelOptions' => ['class' => ''],
    ],
]); ?>

    <div class="col-md-12">
        <?= $form->field($model, 'seo_title',['inputOptions'=>['class'=>'form-control']]) ?>
        <?= $form->field($model, 'seo_keywords',['inputOptions'=>['class'=>'form-control']]) ?>
        <?= $form->field($model, 'seo_description')->textarea(['rows' => 6]) ?>
    </div>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton(Yii::t('app','Save'), ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
            <?= Html::a(Yii::t('app','Back'),['config/index'], ['class' => 'btn btn-default', 'name' => 'login-button']) ?>
        </div>
    </div>
<?php ActiveForm::end(); ?>
</div>

<div class="col-md-offset-1 col-md-4 alert alert-info">
    <h2>SEO 设置说明</h2>
    <p>1、SEO 标题为描述性质，如“专业的XX网站”、“官方网站”等词语，而不是填写网站名称。</p>
    <p>2、SEO 关键字应该为搜索关键字格式应该为 “电脑,网站” ，关键字之间用英文逗号,分隔开。</p>
    <p>3、SEO 描述应为本网站的基本信息描述。P.S. Post页面默认描述信息修改为Post的简介。</p>
</div>
<?=$form->errorSummary($model);?>