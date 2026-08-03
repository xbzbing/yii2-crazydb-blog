<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 */
use yii\web\View;
use yii\helpers\Url;
use app\models\Category;

/* @var View $this */
/* @var $data array */
/* @var $sum int */

$this->params['breadcrumbs'] = ['文章归档',];
$posts = array();
$year_num = array();
$month_num = array();

foreach ($data as $post) {
    list($year, $month, $day) = explode('/', date('Y/m/d', $post['post_time']));
    $posts[$year][$month][$day][$post['id']] = array(
        'cid' => $post['cid'],
        'url' => Url::to(['/post/show', 'name' => $post['alias']]),
        'title' => $post['title'],
        'date' => date('Y/m/d', $post['post_time'])
    );
    $year_num[$year] = isset($year_num[$year]) ? $year_num[$year] + 1 : 1;
}
unset($post);
unset($data);
?>
<style>
    h2 small{padding-left: 5px}
    #archives ul,#archives ol{list-style:none}
    #archives ol li{border-left:none}
    .first{padding-top:10px}
    .else{padding-left:25px}
    .else em{display:none!important}
    .archives>li{font-size:1.1em;border-left:6px solid #0FABF8;line-height:1.5em}
    .archives li em{border-radius:50%;background:#0FABF8;height:40px;width:40px;line-height:40px;color:#FFFFFF;text-align:center;display:inline-block;margin-left:-23px;margin-right:8px;padding-right:3px}
    .archives li:hover>a{text-shadow:0 1px 0 #37a4ff}
    .archives h4,.archives h3{background:#0FABF8;border-radius:20px;height:40px;margin:0 auto 0 -23px;line-height:40px;text-align:center;color:white;font-weight:bolder}
    .archives h3{position:relative;width:80%;min-width:180px;padding-top:0;}
    .archives h4{width:30%;min-width:120px}
    .archives time{color:#999999;font-size:0.8em}
    .archives small{margin-left:10px;color:#999999}
    .archives small a{color:#999999}
    .archives small:hover,.archives small:hover a{color:#4a4a4a}
    .archives .year:hover h3,.archives .month:hover h4{background:orange}
    .year,.month{padding-top:10px;}
    .future em{position:relative;top:-4px}
</style>
<div id="archives">
    <h2>文章归档<small>共 <?=$sum?> 篇</small></h2>
    <ul class="archives">
        <li class="future" title="奋然前行"><em class="glyphicon glyphicon-chevron-up"></em></li>
<?php
if(!empty($posts)){
//仅取顶级分类
$categories = Category::getCategorySummary();
foreach($posts as $year => $post_year){
    $count = $year_num[$year];
    echo "<li class=\"year\"><h3 class=\"year\">{$year} 年（{$count} 篇）</h3></li>";
    foreach($post_year as $month => $post_month){
        $count = (count($post_month,COUNT_RECURSIVE)-count($post_month))/5;
        echo "<li class=\"month\"><h4>{$month}月（{$count} 篇）</h4></li>";
        foreach($post_month as $day => $post_day){
            $index = 1;
            foreach($post_day as $id => $post){
                $css = $index>1?'else':'first';
                $url = $post['url'];
                $title = $post['title'];
                $date = $post['date'];
                if(isset($categories[$post['cid']])){
                    $category = $categories[$post['cid']]['name'];
                    $catUrl = $categories[$post['cid']]['url'];
                }else{
                    $category = '未取得分类';
                    $catUrl = '#';
                }
                echo <<<HTML
        <li class="{$css}">
            <em>{$day}</em>
            <a href="{$url}" title="{$title}" target="_blank">{$title}</a>
            <small>[<a href="$catUrl" title="{$category}" target="_blank">{$category}</a>]</small>
            <time>{$date}</time>
        </li>
HTML;
                $index++;
            }
        }
    }
}
}else{
    echo '<li>&nbsp;&nbsp;暂未发布公开文章</li>';
}
?>
        <li class="previous" title="不忘初心"><em class="glyphicon glyphicon-chevron-down"></em></li>
    </ul>
</div>