<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Category $model
 * @var array $category_array
 */

$this->title = Yii::t('app', 'Update {modelClass}', ['modelClass' => '分类',]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Categories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['breadcrumbs'][] = $model->name
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3>修改分类：<?=$model->name?></h3>
            </div>
            <div class="box-body category-update">
                <?= $this->render('_form', [
                    'model' => $model,
                    'category_array'=>$category_array
                ]) ?>
            </div>
        </div>
    </div>
</div>