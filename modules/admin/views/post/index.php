<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Lookup;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\PostSearch $searchModel
 */

use \app\components\CMSUtils;

$this->title = Yii::t('app', 'Posts');
$this->params['breadcrumbs'][] = $this->title;
$categories = CMSUtils::getAllCategories(true);
?>
<div class="post-index">
    
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Create'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'title',
            [
                'attribute' =>'cid',
                'label'=>'类别',
                'value'=>'postCategory',
                'filter' => Html::activeDropDownList($searchModel,'cid',[''=>'全部']+$categories)
            ],
            'post_time:datetime',
            'view_count',
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
