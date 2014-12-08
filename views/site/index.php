<?php
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 */
use \yii\widgets\LinkPager;
use \app\widgets\CategoryWidget;
use \app\components\CMSUtils;

$this->title = Yii::$app->name;
$posts = $dataProvider->getModels();
?>
<div class="site-index">
<?php
    $categories = CMSUtils::getAllCategories(true);
    $posts = $dataProvider->getModels();
    if(empty($posts)){
        echo '<article class="list-group-item"><h1>暂时没有公开的文章发布，请关注本站更新！</h1></article>';
    }else{
        foreach ($posts as $post){
            echo $this->render('//post/_article', ['post' => $post,'category'=>$categories[$post->cid]]);
        }
    }
?>
    <?=LinkPager::widget(['pagination'=>$dataProvider->getPagination()]);?>
    <?=CategoryWidget::widget(['refresh'=>true])?>
</div>