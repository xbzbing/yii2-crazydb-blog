<?php

use yii\grid\GridView;
use yii\helpers\Html;
use app\models\Log;

/* @var $this yii\web\View */
/* @var $searchModel app\models\LogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '日志管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-header with-border">
        <i class="fa fa-clock-o"></i>
        <h3 class="box-title">日志管理</h3>
        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'label' => '用户',
                    'attribute' => 'uid',
                    'format' => 'html',
                    'value' => function($model, $key, $index, $column){
                        /* @var Log $model */
                        return $model->uid ? Html::a(Html::encode($model->user->username), ['user/view', 'id'=>$model->id], ['target' => '_blank']) : '';
                    }
                ],
                'type',
                'action',
                [
                    'attribute' => 'result',
                    'format' => 'html',
                    'value' => function($model, $key, $index, $column){
                        /* @var Log $model */
                        if($model->result == 'success' || $model->result == '成功')
                            return "<span class=\"label label-success\">{$model->result}</span>";
                        elseif($model->result == 'failed' || $model->result == '失败')
                            return "<span class=\"label label-warning\">{$model->result}</span>";
                        else
                            return $model->result;
                    }
                ],
                [
                    'attribute' => 'create_time',
                    'format' => 'html',
                    'value' => function($model, $key, $index, $column){
                        /* @var Log $model */
                        return date('Y-m-d H:i:s', $model->create_time);
                    }
                ],
                'ip',
                ['class' => 'yii\grid\ActionColumn'],
            ],
        ]); ?>
    </div>
</div>