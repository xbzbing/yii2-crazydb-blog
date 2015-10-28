<?php

use yii\web\View;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\components\CMSUtils;
use app\models\Post;
use crazydb\ueditor\UEditor;

/* @var $this yii\web\View */
/* @var $model app\models\Post */
/* @var $form yii\widgets\ActiveForm */
$this->registerJsFile(\yii\helpers\Url::base(true).'/static/plugins/webuploader/webuploader.min.js')
?>

<div class="post-form">

    <?php $form = ActiveForm::begin(); ?>


    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'alias')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'cover', ['template' => '{label}<div class="input-group"><span class="input-group-btn"><button id="cover-upload" type="button" class="btn btn-success">上传图片</button></span>{input}</div>'])->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'is_top')->dropDownList(['0' => '普通', '1' => '置顶']) ?>

    <?= $form->field($model, 'cid')->dropDownList(CMSUtils::getAllCategories()) ?>

    <?= $form->field($model, 'status')->dropDownList(Post::getAvailableStatus()) ?>

    <?= $form->field($model, 'content')->textarea(['rows' => 6])->widget(UEditor::className(), [
        'config' => [
            'serverUrl' => ['editor/index'],
            'iframeCssUrl' => Yii::getAlias('@web') . '/static/css/ueditor.css',
        ]
    ]) ?>

    <?= $form->field($model, 'abstract')->textarea(['rows' => 6])->widget(UEditor::className(),[
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
        <?= Html::submitButton('保存', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
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
            _csrf: '<?=Yii::$app->request->csrfToken?>'
        },
        server: '<?=\yii\helpers\Url::to(['/tool/image-upload'])?>',
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