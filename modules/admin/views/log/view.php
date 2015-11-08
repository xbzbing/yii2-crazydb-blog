<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Log */

$this->title = '查看日志:' . $model->id;
$this->params['breadcrumbs'][] = ['label' => '日志管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?= DetailView::widget([
            'model' => $model,
            'attributes' => [
                'id',
                'uid',
                'type',
                'action',
                'result',
                'key',
                'detail',
                'create_time:datetime',
                'ip',
                'user_agent',
            ],
        ]) ?>
    </div>
    <div class="box-footer">
        <?= Html::a('返回', ['log/index'], ['class' => 'btn btn-default']) ?>
    </div>
</div>
