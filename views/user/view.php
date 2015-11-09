<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use app\components\XUtils;
use app\models\Category;

/**
 * @var yii\web\View $this
 * @var app\models\User $model
 */

$this->title = $model->username;
$this->params['breadcrumbs'][] = '用户';
$this->params['breadcrumbs'][] = $model->nickname;
?>
<div id="author-info">
    <div class="author-info">
        <div class="user-avatar pull-left">
            <?= '<img src="', XUtils::getAvatar($model->email, 100) . '" class="thumbnail"/>'; ?>
        </div>
        <div class="user-info pull-left">
            <table class="table table-hover table-striped">
                <tr>
                    <td>用户昵称：</td>
                    <td><strong><?= $model->nickname ?></strong></td>
                </tr>
                <tr>
                    <td>电子邮箱：</td>
                    <td><em><?= str_replace('@', '#', $model->email) ?></em></td>
                </tr>
                <tr>
                    <td>Web Site:</td>
                    <td><em><?= $model->website ?></em></td>
                </tr>
            </table>
        </div>
        <div class="user-info pull-left">
            <div><?= $model->info; ?></div>
        </div>
    </div>
</div>
<footer class="entry-footer row">
    <span class="col-md-offset-1 pull-left">
        <?= $model->nickname ?> 共发表 <strong><?= $dataProvider->totalCount ?></strong> 篇文章。
    </span>
</footer>
<?php
$categories = Category::getCategorySummary();
foreach ($dataProvider->getModels() as $post) {
    echo $this->render('//post/_article', ['post' => $post, 'category' => $categories[$post->cid], 'extCss' => 'row']);
}
?>
<div class="panel-footer row">
    <?= LinkPager::widget([
        'pagination' => $dataProvider->pagination,
        'nextPageLabel' => '下一页',
        'prevPageLabel' => '上一页',
        'firstPageLabel' => '首页',
        'lastPageLabel' => '末页',
        'registerLinkTags' => true
    ]); ?>
</div>
