<?php

use yii\helpers\Html;
use yii\grid\GridView;

use app\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '用户管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box user-index">
	<div class="box-header with-border">
		<h3 class="box-title">用户列表</h3>
	</div>
	<div class="box-body">
		<?= GridView::widget([
			'dataProvider' => $dataProvider,
			'filterModel' => $searchModel,
			'columns' => [
				['class' => 'yii\grid\SerialColumn'],
				[
					'attribute' => 'nickname',
					'format' => 'html',
					'value' => function($model, $key, $index, $column){
						return Html::a($model->nickname, ['user/view', 'id' => $model->id]);
					},
				],
				'id',
				[
					'attribute' => 'role',
					'format' => 'html',
					'filter' => Html::activeDropDownList($searchModel, 'role', ['' => '全部'] + User::getAvailableRole(), ['class' => 'form-control']),
				],
				'email',
				[
					'format' => 'html',
					'attribute' => 'status',
					'value' => function($model, $key, $index, $column){
						/* @var User $model */
						if($model->status === User::STATUS_BANED || $model->status === User::STATUS_DELETED)
							return "<span class=\"label label-danger\">{$model->userStatus}</span>";
						elseif($model->status === User::STATUS_INACTIVE)
							return "<span class=\"label label-default\">{$model->userStatus}</span>";
						elseif($model->status === User::STATUS_NORMAL)
							return $model->userStatus;
						else
							return "<span class=\"label label-warning\">{$model->userStatus}</span>";
					},
					'filter' => Html::activeDropDownList($searchModel, 'status', ['' => '全部'] + User::getAvailableStatus(), ['class' => 'form-control']),
				],
				[
					'label' => '注册时间',
					'value' => function($model, $key, $index, $column){
						return date('Y-m-d H:i:s', $model->register_time);
					},
				],
				[
					'class' => 'yii\grid\ActionColumn',
					'template' => '{view}&nbsp;&nbsp;{update}'
				],
			],
		]); ?>
	</div>
</div>


