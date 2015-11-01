<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 */
use yii\web\View;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;
use yii\widgets\LinkPager;
use app\models\Post;
use app\models\Category;

/* @var View $this */
/* @var Post $posts */
/* @var string $tag */
/* @var ActiveDataProvider $dataProvider */

$this->params['breadcrumbs'] = [
    ['label' => '所有标签', 'url' => Url::to(['tag/list'])],
    '标签'
];
$sum = $dataProvider->totalCount;
$this->title = '标签：' . $tag;
$seo_description = "标签：{$tag}。该标签下共有{$sum}篇文章，";
$categories = Category::getAllCategories();
$post_names = array();
echo <<<HTML5
<header class="tag-info list-header">
    <h1><em class="glyphicon glyphicon-tag"></em>{$tag}  <small>共 {$sum} 篇</small></h1>
</header>
HTML5;
$posts = $dataProvider->models;
foreach ($posts as $post) {
    echo $this->render('/post/_article', array('post' => $post, 'category' => $categories[$post->cid]));
    $post_names[] = $post->title;
}
echo LinkPager::widget(['pagination' => $dataProvider->pagination]);

if(empty($this->params['seo_keywords']))
    $this->params['seo_keywords'] = $tag;
else
    $this->params['seo_keywords'] = $tag . ',' . $this->params['seo_keywords'];
$this->params['seo_description'] = $seo_description . '当前页面有以下文章：' . implode('、', $post_names) . '。';
