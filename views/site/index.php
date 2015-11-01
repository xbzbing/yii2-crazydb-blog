<?php
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 */
use yii\widgets\LinkPager;
use app\models\Category;

$this->title = Yii::$app->name;
$posts = $dataProvider->getModels();
?>
<?php
$categories = Category::getAllCategories();
$posts = $dataProvider->getModels();
if (empty($posts)) {
    echo '<article class="list-group-item"><h1>暂时没有公开的文章发布，请关注本站更新！</h1></article>';
} else {
    foreach ($posts as $post) {
        echo $this->render('//post/_article', ['post' => $post, 'category' => $categories[$post->cid]]);
    }
}
?>
<?= LinkPager::widget(['pagination' => $dataProvider->pagination]); ?>