<?php
use yii\web\View;
use yii\data\ActiveDataProvider;
use yii\widgets\LinkPager;
use app\models\Category;
/**
 * 显示多个 post 的页面
 * @var View $this
 * @var ActiveDataProvider $dataProvider
 */

$categories = Category::getCategorySummary();
$posts = $dataProvider->getModels();
if (empty($posts)) {
    echo '<article class="list-group-item"><h1>暂时没有公开的文章发布，请关注本站更新！</h1></article>';
} else {
    foreach ($posts as $post) {
        echo $this->render('_article', ['post' => $post, 'category' => $categories[$post->cid]]);
    }
}
?>
<div class="panel-footer">
<?= LinkPager::widget([
    'pagination' => $dataProvider->pagination,
    'nextPageLabel' => '下一页',
    'prevPageLabel' => '上一页',
    'firstPageLabel' => '首页',
    'lastPageLabel' => '末页',
]);?>
</div>
