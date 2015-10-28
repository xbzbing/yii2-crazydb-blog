<?php
use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Category;
use app\models\Post;
/* @var $this yii\web\View */
/* @var $searchModel app\models\PostSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '文章管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box post-index">
    <div class="box-header with-border">
        <h3 class="box-title">文章列表</h3>
    </div>
    <div class="box-body">
        <p>
            <?= Html::a('发表新文章', ['create'], ['class' => 'btn btn-success']) ?>
        </p>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'tableOptions' => ['class'=>'table table-bordered table-hover'],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                'title',
                [
                    'attribute' => 'cid',
                    'value' => 'postCategory',
                    'filter' => Html::activeDropDownList($searchModel, 'cid', ['' => '全部'] + Category::getAllCategories(), ['class' => 'form-control']),
                ],
                [
                    'attribute' => 'author_id',
                    'value' => function ($model, $key, $index, $column) {
                        return $model->author ? $model->author->nickname : null;
                    }
                ],
                [
                    'attribute' => 'is_top',
                    'format' => 'html',
                    'value' => function ($model, $key, $index, $column) {
                        return $model->is_top ? '<span class="label label-success">置顶</span>' : '';
                    },
                    'filter' => Html::activeDropDownList($searchModel, 'is_top', ['' => '全部', '0' => '否', '1' => '是'], ['class' => 'form-control']),
                ],
                [
                    'attribute' => 'status',
                    'value' => 'postStatus',
                    'filter' => Html::activeDropDownList($searchModel, 'status', ['' => '全部'] + Post::getAvailableStatus(), ['class' => 'form-control']),
                ],
                [
                    'attribute' => 'comment_count',
                    'filter' => false,
                ],
                [
                    'attribute' => 'view_count',
                    'filter' => false
                ],
                [
                    'attribute' => 'post_time',
                    'format' => ['date', 'php:Y-m-d H:i:s'],
                    'filter' => false,
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view}&nbsp;&nbsp;{update}'
                ],
            ],
        ]); ?>
    </div>
</div>