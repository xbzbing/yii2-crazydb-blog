<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\User;
/**
 * @var yii\web\View $this
 * @var User $model
 */

$this->title = '用户: ' . $model->username;
$this->params['breadcrumbs'][] = ['label' => '用户管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box user-view">
	<div class="box-header with-border">
		<?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
		<?= Html::a('删除', ['delete', 'id' => $model->id], [
			'class' => 'btn btn-danger',
			'data' => [
				'confirm' => 'Are you sure you want to delete this item?',
				'method' => 'post',
			],
		]) ?>
		<div class="box-tools pull-right">
			<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
			<button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
		</div>
	</div>
	<div class="box-body">
		<?= DetailView::widget([
			'model' => $model,
			'attributes' => [
				'avatar:image',
				'username',
				'nickname',
				'email',
				'userRole',
				'userStatus',
				'info',
				'register_time:datetime',
				'register_ip',
			],
		]) ?>
	</div>
	<div class="box-footer">
		<?= Html::a('返回', ['user/index'], ['class' => 'btn btn-default']) ?>
	</div>
</div>