<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Category $model
 * @var array $category_array
 */

$this->title = Yii::t('app', 'Update {modelClass}: ', [
  'modelClass' => 'Category',
]) . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Categories'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="category-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'category_array'=>$category_array
    ]) ?>

</div>
