<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 */

use yii\web\View;
use yii\helpers\Url;
use app\models\Category;
/* @var View $this */

$categories = Category::getCategorySummary();
 ?>
<div class="widget aside-categories">
    <h3 class="widget_title with-shadow">
        <i class="glyphicon glyphicon-list"></i>分类文章
        <a href="<?= Url::to(['feed/rss']) ?>" target="_blank" title="RSS订阅"><em class="rss-feed"></em></a>
    </h3>
    <ul class="with-shadow">
        <?php
        foreach($categories as $category){
            if($category['postCount']<1)
                continue;
            $desc = trim($category['desc']);
            if(empty($desc) || strlen($desc) > 100)
                $desc = $category['name'];
            echo "<li><a href=\"{$category['url']}\" title=\"{$desc}\">{$category['name']}</a>（{$category['postCount']} 篇）</li>";
        }
        ?>
        <li>
            <em class="glyphicon glyphicon-hand-right"></em>&nbsp;&nbsp;
            <a href="<?= Url::to(['post/archives'])?>" target="_blank">文章归档</a>
        </li>
    </ul>
</div>