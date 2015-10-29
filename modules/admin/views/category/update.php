<?php


/**
 * @var yii\web\View $this
 * @var app\models\Category $model
 * @var array $category_array
 */

$this->title = '更新分类';
$this->params['breadcrumbs'][] = ['label' => '所有分类', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['breadcrumbs'][] = $model->name;
echo $this->render('_form', [
    'model' => $model,
    'category_array'=>$category_array
]);