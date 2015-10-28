<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Post */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => '文章管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box post-view">
    <div class="box-header with-border">
        <h3 class="box-title">文章详情</h3>
        <div class="box-tools pull-right">
            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
            <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
        </div>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover">
            <tbody>
            <tr>
                <td width="120">文章标题</td>
                <td><?=$model->title?></td>
            </tr>
            <tr>
                <td>分类</td>
                <td><?=$model->postCategory?></td>
            </tr>
            <tr>
                <td>作者</td>
                <td><?=Html::a($model->author->nickname, Url::to(['user/view', 'id'=>$model->author_id]))?></td>
            </tr>
            <tr>
                <td>创建时间</td>
                <td><?=date('Y-m-d H:i:s', $model->create_time)?></td>
            </tr>
            <tr>
                <td>更新时间</td>
                <td><?=date('Y-m-d H:i:s', $model->update_time)?></td>
            </tr>
            <tr>
                <td>发表时间</td>
                <td><?=date('Y-m-d H:i:s', $model->post_time)?></td>
            </tr>
            <tr>
                <td>点击数量</td>
                <td><?= $model->view_count ?></td>
            </tr>
            <tr>
                <td>置顶</td>
                <td><?=$model->is_top?'<span class="label label-success">置顶</span>':'否'?></td>
            </tr>
            </tbody>
        </table>
        <div class="content entry-content">
            <?=$model->content?>
        </div>
    </div>
    <div class="box-footer">
        <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('删除', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => '删除操作不可恢复，确定删除?',
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a('前台查看', $model->url, ['target' => '_blank', 'class' => 'btn btn-success'])?>
        <?= Html::a('返回列表', ['post/index'], ['class' => 'btn btn-default']) ?>
    </div>
</div>
