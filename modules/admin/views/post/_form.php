<?php

use yii\bootstrap\ActiveForm;
use app\components\CMSUtils;
use yii\helpers\Html;
use yii\helpers\Url;
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
    <div class="col-md-9" style="padding-left:0px;">
        <?php $form = ActiveForm::begin(); ?>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
          <li class="active"><a href="#home" data-toggle="tab"><?= Yii::t('app', 'Content') ?></a></li>
          <li><a href="#seo" data-toggle="tab"><?= Yii::t('app', 'SEO') ?></a></li>
        </ul>
        <br>
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="home">
                <div class="col-md-8">
                    <?= $form->field($model, 'title')->textInput() ?>
                    <?= $form->field($model, 'alias')->textInput() ?>
                    <?= $form->field($model, 'tags')->textInput() ?>
                    <div class="form-group">
                        <?= $form->field($model, 'cid', ['options'=>['class'=>'col-md-6']])->dropDownList($categories) ?>
                        <?= $form->field($model, 'status', ['options'=>['class'=>'col-md-6']])->dropDownList(Post::getAvailableStatus()) ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <?php if (!empty($model->thumbnail)): ?>
                                <button type="button" class="close" aria-hidden="true" id="thumbnail-delete">&times;</button>
                            <?php endif ?>
                            <img id="thumbnail" class="media-object" data-src="holder.js/194x194" alt="thumbnail" title="thumbnail" src="<?= !empty($model->cover)?$model->cover:"data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxOTMiIGhlaWdodD0iMTkzIj48cmVjdCB3aWR0aD0iMTkzIiBoZWlnaHQ9IjE5MyIgZmlsbD0iI2VlZSI+PC9yZWN0Pjx0ZXh0IHRleHQtYW5jaG9yPSJtaWRkbGUiIHg9Ijk2IiB5PSI5NiIgc3R5bGU9ImZpbGw6I2FhYTtmb250LXdlaWdodDpib2xkO2ZvbnQtc2l6ZToxOHB4O2ZvbnQtZmFtaWx5OkFyaWFsLEhlbHZldGljYSxzYW5zLXNlcmlmO2RvbWluYW50LWJhc2VsaW5lOmNlbnRyYWwiPnRodW1ibmFpbDwvdGV4dD48L3N2Zz4=" ?>" class="img-rounded" style="max-width:194px;max-height:194px;">
                            <input class="ke-input-text" type="text" id="url" value="" readonly="readonly" /> <input type="button" id="uploadButton" value="<?= Yii::t('app', 'Upload') ?>" />
                            <?= Html::activeHiddenInput($model, 'cover') ?>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <?php $model->isNewRecord?$model->post_time=date("Y-m-d H:i:s"):$model->post_time=date("Y-m-d H:i:s", $model->post_time);?>
                            <?= $form->field($model, 'post_time')->textInput(['maxlength' => 255]) ?>
                            <button id="set-it-now" type="button" class="btn btn-default form-control"><?= Yii::t('app', 'Set It Now') ?></button>
                        </div>
                    </div>

                </div>
                <br clear="both">
                <?= $form->field($model, 'content')->widget(UEditor::className())->label(false) ?>
                <?= $form->field($model, 'excerpt')->textArea(['rows' => 5]) ?>
            </div>
            <div class="tab-pane" id="seo">
                SEO
            </div>
        </div>

        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    </div>


    <?php ActiveForm::end(); ?>
</div>

<?php
$this->registerJs('
jQuery(".post-form").on("click", "#thumbnail-delete",function(){
    $.get("'.Url::to(['post/thumbnail-delete', 'id' => $model->id]).'", function(data){jQuery("#thumbnail").attr("src", "");})
});
jQuery(".post-form").on("click", "#set-it-now",function(){ jQuery("#post-published_at").prop("value","'.date('Y-m-d H:i:s').'") });
');
?>