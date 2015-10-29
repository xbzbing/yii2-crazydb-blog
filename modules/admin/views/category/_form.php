<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Category;

use crazydb\ueditor\UEditor;

/**
 * @var yii\web\View $this
 * @var app\models\Category $model
 * @var yii\widgets\ActiveForm $form
 * @var array $category_array
 */
?>
<div class="category-form">
    <?php $form = ActiveForm::begin(); ?>
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#basic-setting" data-toggle="tab" aria-expanded="true">基本信息</a></li>
            <li class=""><a href="#seo-setting" data-toggle="tab" aria-expanded="false">SEO 设置</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="basic-setting">
                <?= $form->field($model, 'name')->textInput(['maxlength' => 255, 'tabIndex'=>1]) ?>
                <div class="row">
                    <div class="col-md-12 col-lg-4">
                        <?= $form->field($model, 'alias')->textInput(['maxlength' => 100]) ?>
                    </div>
                    <div class="col-md-12 col-lg-4">
                        <?= $form->field($model, 'sort_order')->input('number') ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 col-lg-4">
                        <?= $form->field($model, 'parent')->dropDownList($category_array) ?>
                    </div>
                    <div class="col-md-12 col-lg-4">
                        <?= $form->field($model, 'display')->dropDownList(Category::getAvailableDisplay()) ?>
                    </div>
                </div>

                <?= $form->field($model, 'desc')->textarea(['rows' => 6])->widget(UEditor::className(),[
                    'config'=>[
                        'toolbars'=>[
                            ['source','link','bold','italic','underline','forecolor','superscript','insertimage','spechars','blockquote']
                        ],
                        'initialFrameHeight'=>'150',
                    ]
                ]) ?>

            </div>
            <div class="tab-pane" id="seo-setting">
                <?= $form->field($model, 'seo_title')->textInput(['maxlength' => 100]) ?>
                <?= $form->field($model, 'seo_keywords')->textInput(['maxlength' => 255]) ?>
                <?= $form->field($model, 'seo_description')->textarea(['rows' => 6]) ?>
            </div>
        </div>
        <div class="form-group box-footer">
            <?= Html::submitButton($model->isNewRecord ? '创建' : '保存', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            <?= Html::a('返回',Yii::$app->request->referrer,['class'=>'btn btn-default'])?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
