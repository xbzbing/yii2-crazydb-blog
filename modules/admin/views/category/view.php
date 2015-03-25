<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var app\models\Category $model
 */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Categories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <p>
                    <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?= Html::a(Yii::t('app', 'Back'), ['index'], ['class' => 'btn btn-default']) ?>
                </p>
            </div>
            <div class="box-body category-view">
                <?=DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'id',
                        'name',
                        'alias',
                        'displayType',
                        'desc',
                        [
                            'attribute' => 'parent',
                            'value' => $model->parent?Html::a($model->parentCategory->name,$model->parentCategory->url):'顶级分类',
                            'label' => $model->parent?'上级分类':'分类类型',
                            'format' => 'raw'
                        ],
                        'display',
                        'sort_order',
                        'seo_title',
                        'seo_keywords',
                        'seo_description',
                        ]
                ]) ?>
            </div>
        </div>
    </div>
</div>
