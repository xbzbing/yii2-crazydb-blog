<?php

/**
 * @var yii\web\View $this
 * @var app\models\Category $model
 * @var array $category_array
 */

$this->title = '增加分类';
$this->params['breadcrumbs'][] = ['label' => '所有分类', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
echo $this->render('_form', [ 'model' => $model, 'category_array' => $category_array]);