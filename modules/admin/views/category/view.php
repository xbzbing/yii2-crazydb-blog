<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var app\models\Category $model
 */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => '分类管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <p>
                    <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('删除', ['delete', 'id' => $model->id], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => '删除操作不可恢复,确定删除?',
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?= Html::a('返回', ['index'], ['class' => 'btn btn-default']) ?>
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
                        'keywords',
                        ]
                ]) ?>
            </div>
        </div>
    </div>
</div>
