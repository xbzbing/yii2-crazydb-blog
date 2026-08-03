<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 */

use yii\web\View;
use yii\helpers\Url;
use app\models\Tag;

/* @var View $this */
$tags = Tag::getTags(false, 20);

$colors = ['default', 'primary', 'success', 'info', 'warning', 'danger'];
?>
<div class="widget aside-tags">
    <h3 class="widget_tit with-shadow">
        <i class="glyphicon glyphicon-tags"></i>标签
    </h3>

    <div class="with-shadow">
        <?php
        if ($tags) {
            foreach ($tags as $tag) {
                $color = $colors[mt_rand(0, 5)];
                echo <<<HTML
        <a href="{$tag['url']}" title="{$tag['name']}({$tag['totalCount']})" target="_blank">
            <span class="label label-{$color}">{$tag['name']}</span>
        </a>
HTML;
            }
        } else {
            echo '<span class="label label-default">暂无标签</span>';
        }
        ?>
        <div class="more-link">
            <em class="glyphicon glyphicon-hand-right"></em>&nbsp;&nbsp;
            <a href="<?= Url::to(['tag/list']) ?>" target="_blank" title="所有标签">所有标签</a>
        </div>
    </div>
</div>
