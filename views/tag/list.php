<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 */
use yii\web\View;
use yii\helpers\Url;

/* @var View $this */
/* @var array $tags */

$this->params['breadcrumbs'] = [
    '标签'
];
$tag_array = array();
$sum = count($tags);
echo <<<HTML
<header class="tag-info list-header">
    <h1><em class="glyphicon glyphicon-tag"></em>所有标签  <small>共 {$sum} 个标签</small></h1>
</header>
<article class="tags-list">
<ul class="tags">
HTML;
$half = floor($sum / 2);
$index = 1;
if ($sum < 10)
    $half = $index = $sum + 1;
foreach ($tags as $tag) {
    $tag_array[] = $tag['name'];
    echo "<li><a href=\"{$tag['url']}\" target=\"_blank\" title=\"{$tag['name']}\">{$tag['name']}</a><small>( {$tag['totalCount']} )</small></li>";
}
echo <<<HTML
    </ul>
</article>
HTML;
$this->params['seo_keywords'] .= implode('、', $tag_array) . '。';
