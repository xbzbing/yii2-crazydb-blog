<?php
/**
 * 显示多个 post 的页面
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var string $date
 */
$date = date('Y年m月', strtotime($date));
echo <<<HTML5
<header class="tag-info list-header">
    <h1><em class="glyphicon glyphicon-list"></em>{$date}  <small>共 {$dataProvider->totalCount} 篇</small></h1>
</header>
HTML5;
echo $this->render('posts', ['dataProvider' => $dataProvider]);