<?php

use yii\web\View;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use app\models\Category;
use app\models\Post;
use crazydb\ueditor\UEditor;

/* @var $this yii\web\View */
/* @var $model app\models\Post */
/* @var $form yii\widgets\ActiveForm */
$this->registerJsFile(Url::base(true).'/static/plugins/webuploader/webuploader.min.js')
?>
<div class="post-form">

    <?php $form = ActiveForm::begin(); ?>

    <?=$form->errorSummary($model, ['class' => 'alert alert-warning'])?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    <div class="row">
        <div class="col-md-12 col-lg-4">
            <?= $form->field($model, 'alias')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-12 col-lg-4">
            <?= $form->field($model, 'author_name')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <?php if ($model->scenario === Post::SCENARIO_MANAGE):?>
    <div class="row">
        <div class="col-md-12 col-lg-4">
            <?= $form->field($model, 'author_id')->textInput(['maxlength' => true])->label('作者: ' . $model->author->nickname)  ?>
        </div>
        <div class="col-md-12 col-lg-4">
            <?= $form->field($model, 'view_count')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="row">
        <?php
        $template = '{label}<div class="input-group"><span class="input-group-addon bg-default">'
            . Html::activeCheckbox($model, 'auto_cover')
            . '</span>{input}<span class="input-group-btn"><button id="cover-upload" type="button" class="btn btn-success">上传图片</button></span></div>';
        echo $form->field($model, 'cover', ['template' => $template, 'options' => ['class' => 'col-md-8']])
            ->textInput(['maxlength' => true]);
        ?>
    </div>
    <div class="row">
        <div class="col-md-12 col-lg-3">
            <?= $form->field($model, 'cid')->dropDownList(Category::getAllCategories()) ?>
        </div>
        <div class="col-md-12 col-lg-3">
            <?= $form->field($model, 'status')->dropDownList(Post::getAvailableStatus()) ?>
        </div>
        <div class="col-md-12 col-lg-3">
            <?= $form->field($model, 'type')->dropDownList(Post::getAvailableType()) ?>
        </div>
        <div class="col-md-12 col-lg-3">
            <?= $form->field($model, 'is_top')->dropDownList(['0' => '普通', '1' => '置顶']) ?>
        </div>
    </div>
    <?= $form->field($model, 'content', ['enableClientValidation' => false])->textarea(['rows' => 6])->widget(UEditor::className(), [
        'config' => [
            'serverUrl' => ['editor/index'],
            'iframeCssUrl' => Yii::getAlias('@web') . '/static/css/ueditor.css',
        ]
    ]) ?>

    <?= $form->field($model, 'excerpt')->textarea(['rows' => 6])->widget(UEditor::className(),[
        'config'=>[
            'toolbars'=>[
                ['source','link','bold','italic','underline','forecolor','superscript','insertimage','spechars','blockquote','removeformat','pasteplain']
            ],
            'initialFrameHeight'=>'150',
            'serverUrl' => ['editor/index'],
            'iframeCssUrl' => Yii::getAlias('@web') . '/static/css/ueditor.css',
        ]
    ]) ?>

    <div class="form-group">
        <?= Html::submitButton('保存', ['class' => 'btn btn-success']) ?>
        <?= Html::a('返回', ['post/index'], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<script>
    <?php
    ob_start();
    ?>
    var cover_uploader = WebUploader.create({
        auto: true,
        formData: {
            _csrf: '<?= Yii::$app->request->csrfToken ?>'
        },
        server: '<?= Url::to(['/tool/image-upload']) ?>',
        pick: '#cover-upload',
        accept: {
            title: '请选择图片',
            extensions: 'gif,jpg,jpeg,bmp,png',
            mimeTypes: 'image/*'
        }
    });
    cover_uploader.on('uploadSuccess', function (file, response) {
        $('#<?=Html::getInputId($model, 'cover')?>').val(response.url);
    });
    cover_uploader.on('uploadError', function (file) {
        alert('上传出错');
    });
    <?php
    $script = ob_get_contents();
    ob_end_clean();
    $this->registerJs($script, View::POS_READY, 'cover_uploader');
    ?>
</script>