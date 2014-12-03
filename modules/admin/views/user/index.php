<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\User;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\search\UserSearch $searchModel
 */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

	<h1><?= Html::encode($this->title) ?></h1>

	<?php // echo $this->render('_search', ['model' => $searchModel]); ?>

	<p>
        <?= Html::a(Yii::t('app', 'Create {modelClass}', [
  'modelClass' => 'User',
]), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

	<?= GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => [
			['class' => 'yii\grid\SerialColumn'],

			'id',
			'username',
			'email',
			'userStatus',
			'reg_time:datetime',

			['class' => 'yii\grid\ActionColumn'],
		],
	]); ?>

</div>
