<?php
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 */
$this->title = '缓存管理';
$this->params['breadcrumbs'][] = ['label'=>'系统设置','url'=>'#'];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">缓存管理</h3>
        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?= Html::a('清空所有缓存', ['config/cache', 'action' => 'clear_all'], [
            'class' => 'btn btn-success',
            'data' => [
                'confirm' => '缓存可能共用,清空全部缓存可能会影响系统稳定.确定清楚?',
                'method' => 'post',
            ],
        ]) ?>
    </div>
    <div class="box-footer">
        <?= Html::a('返回',['default/index'], ['class' => 'btn btn-default', 'name' => 'login-button']) ?>
    </div>
</div>
