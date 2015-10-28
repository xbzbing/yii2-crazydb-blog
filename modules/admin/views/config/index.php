<?php
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 */
$this->title = '系统设置';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">系统设置</h3>
        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?= Html::a('基本设置', ['config/setting'], ['class' => 'btn btn-default']) ?>
        <?= Html::a('SEO设置', ['config/seo'], ['class' => 'btn btn-default']) ?>
        <?= Html::a('缓存设置', ['config/cache'], ['class' => 'btn btn-default']) ?>
    </div>
</div>