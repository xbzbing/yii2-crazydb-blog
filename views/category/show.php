<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 */
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use app\models\Category;
use app\models\Post;

/* @var yii\web\View $this */
/* @var Category $model */

$this->params['breadcrumbs'] = [
    ['label' => '文章归档', 'url' => Url::to(['post/archives'])],
    '分类文章'
];
$dataProvider = new ActiveDataProvider([
    'query' => Post::find()
        ->where(['cid' => $model->id, 'status' => [Post::STATUS_PUBLISHED, Post::STATUS_HIDDEN]])
        ->orderBy(['post_time' => SORT_DESC])
]);

echo <<<HTML5
<header class="tag-info list-header">
    <h1><em class="glyphicon glyphicon-list"></em>{$model->name}  <small>共 {$dataProvider->totalCount} 篇</small></h1>
</header>
HTML5;
echo $this->render('//post/posts', ['dataProvider' => $dataProvider]);
