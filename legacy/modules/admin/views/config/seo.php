<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var app\modules\admin\models\SeoForm $model
 */
$this->title = 'SEO设置';
$this->params['breadcrumbs'][] = ['label'=>'系统设置','url'=>'#'];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <?php
    $form = ActiveForm::begin([
        'id' => 'setting-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "<div>{label}{input}</div>\n<div class=\"col-lg-12\">{error}</div>",
            'labelOptions' => ['class' => ''],
        ],
    ]); ?>
    <div class="box-header with-border">
        <h3 class="box-title">SEO设置</h3>
        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
        </div>
    </div>
    <div class="box-body">
        <div class="col-md-6">
            <?= $form->field($model, 'seo_title',['inputOptions'=>['class'=>'form-control']]) ?>
            <?= $form->field($model, 'seo_keywords',['inputOptions'=>['class'=>'form-control']]) ?>
            <?= $form->field($model, 'seo_description')->textarea(['rows' => 6]) ?>
        </div>

        <div class="col-md-offset-1 col-md-5 alert alert-info">
            <h2>SEO 设置说明</h2>
            <p>1、SEO 标题为描述性质，如“专业的XX网站”、“官方网站”等词语，而不是填写网站名称。</p>
            <p>2、SEO 关键字应该为搜索关键字格式应该为 “电脑,网站” ，关键字之间用英文逗号,分隔开。</p>
            <p>3、SEO 描述应为本网站的基本信息描述。P.S. Post页面默认描述信息修改为Post的简介。</p>
        </div>
        <?=$form->errorSummary($model);?>
        <div class="clearfix"></div>
    </div>
    <div class="box-footer">
        <?= Html::submitButton('保存', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        <?= Html::a('返回管理首页',['default/index'], ['class' => 'btn btn-default', 'name' => 'login-button']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
