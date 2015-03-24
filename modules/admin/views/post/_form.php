<?php

use yii\bootstrap\ActiveForm;
use app\components\CMSUtils;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use app\models\Category;
use app\models\Post;

use crazydb\ueditor\UEditor;
/**
 * @var yii\web\View $this
 * @var Post $model
 * @var yii\widgets\ActiveForm $form
 */
$category = new Category;
$Categories = $category->find()->all();
$level = 0;

$arr[0] = Yii::t('app', 'Please select the Category');
$categories = CMSUtils::getAllCategories();
?>
<div class="post-form">
    <?php $form = ActiveForm::begin(); ?>
    <div class="panel panel-default top-padding-13 post-create">
        <div class="col-md-8">
            <?= $form->field($model, 'title')->textInput() ?>
            <?= $form->field($model, 'alias')->textInput() ?>
            <?= $form->field($model, 'tags')->textInput() ?>
            <?= $form->field($model, 'cid')->dropDownList($categories) ?>
            <?= $form->field($model, 'status')->dropDownList(Post::getAvailableStatus()) ?>
            <?php if(!$model->isNewRecord):?>
                <?= $form->field($model, 'post_time')->textInput(['value'=>date('Y-m-d H:i:s')]) ?>
                <button id="set-it-now" type="button" class="btn btn-default form-control">
                    <?= Yii::t('app', 'Set It Now') ?>
                </button>
            <?php endif;?>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-body">
                    <label for="post-cover">文章封面图片</label>
                    <div class="input-group">
                        <input type="text" id="post-cover" class="form-control" name="Post[cover]" placeholder="封面图片地址" value="<?=$model->cover?>">
                        <span class="input-group-btn">
                            <button type="button" onclick="uploadFile()" class="btn btn-info">上传</button>
                        </span>
                    </div>
                    <br clear="both">
                    <div id="dropbox">
                        <input type="file" accept="image/*" name="upfile" id="fileToUpload" onchange="fileSelected();">
                        <?php if($model->cover):?>
                        <div class="preview" id="preview">
                            <div class="imageHolder">
                                <img src="<?=$model->cover?>" width="200px">
                            </div>
                        </div>
                        <?php endif;?>
                    </div>
                    <div class="progress progress-striped active">
                        <div class="bar" id="uploadProcess"></div>
                    </div>
                </div>
            </div>
        </div>
        <br clear="both">
        <?= $form->field($model, 'content')->widget(UEditor::className())->label(false) ?>
        <?= $form->field($model, 'excerpt')->widget(UEditor::className(),[
            'config'=>[
            'toolbars'=>[
                ['source','link','bold','italic','underline','forecolor','superscript','insertimage','spechars','blockquote']
            ],
            'initialFrameHeight'=>'150',
            ]
        ])?>
    </div>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Back'),Url::to(['post/index']),['class'=>'btn btn-default'])?>
    </div>
</div>


<?php ActiveForm::end(); ?>

<?php
$time = <<<JS
jQuery("#set-it-now").click(function(){
    var date = new Date();
    var post_time = date.getFullYear() + "-";
    post_time += date.getMonth() + "-";
    post_time += date.getDate() + " ";
    post_time += date.getHours() + ":";
    post_time += date.getMinutes() + ":";
    post_time += date.getSeconds();
    jQuery("#post-post_time").prop("value",post_time);
});
JS;
$this->registerJs($time,View::POS_READY);

?>
<script>
    var template = '<div id="preview" class="preview">'+
        '<div class="imageHolder">'+
        '<span class="uploaded"></span>'+
        '</div>'+
        '</div>';
    var dropbox = $('#dropbox'),
        message = $('.message', dropbox);

    function fileSelected() {
        var file = document.getElementById("fileToUpload").files[0];
        if (file) {
            if($("#post-cover").val() || $("#preview").length>0){
                if(!confirm("已经存在封面图片，\n是否要重新上传？"))
                    return false;
            }
            createImage(file);
        }
    }
    function createImage(file){
        $("#preview").remove();
        var preview = $(template),
            imageHolder = $(".imageHolder", preview);
        var image = document.createElement("img");
        var reader = new FileReader();
        reader.onload = function(e){
            image.src = e.target.result;
        };
        image.setAttribute("width","200px");
        image.setAttribute("height","200px");
        reader.readAsDataURL(file);
        imageHolder.append(image);
        preview.appendTo(dropbox);
    }

    function uploadFile() {
        if($("#post-cover").val()){
            if(!confirm("已经存在封面图片，\n是否要重新上传？"))
                return false;
        }
        var file = document.getElementById("fileToUpload").files[0];
        if(!file){
            alert("请选择要上传的图片！");
            return false;
        }
        var fd = new FormData();
        fd.append("upfile", file);
        var xhr = new XMLHttpRequest();
        xhr.upload.addEventListener("progress", uploadProgress, false);
        xhr.addEventListener("load", uploadComplete, false);
        xhr.addEventListener("error", uploadFailed, false);
        xhr.addEventListener("abort", uploadCanceled, false);
        xhr.open("POST", "<?=Url::to(['/ueditor','action'=>'uploadimage','encode'=>'utf-8']);?>");
        xhr.send(fd);
    }

    function uploadProgress(evt) {
        if (evt.lengthComputable) {
            var percentComplete = Math.round(evt.loaded * 100 / evt.total);
            $("#uploadProcess").attr("style","width:"+percentComplete.toString() + "%");
            if(percentComplete==100){
                $("#preview").addClass("done");
            }
        }
    }

    function uploadComplete(evt) {
        var response = eval("("+evt.target.responseText+")");

        if(response.state=="SUCCESS"){
            $("#post-cover").val(response.thumbnail);
        }else{
            alert("上传失败:\n"+response.error);
        }
    }

    function uploadFailed(evt) {
        alert("上传失败！");
    }

    function uploadCanceled(evt) {
        alert("上传被取消");
    }
</script>