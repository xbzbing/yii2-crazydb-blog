<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 */
use yii\web\View;
use app\models\Comment;
use app\components\XUtils;
/* @var View $this */
$comments = Comment::getRecentComments(5);
//有留言就显示，没有则完全不显示
if($comments):
?>
<div class="widget aside-comments hidden-xs">
    <h3 class="widget_title with-shadow"><i class="glyphicon glyphicon-comment"></i>最新评论</h3>
    <ul class="with-shadow">
<?php
foreach($comments as $comment){
    $comment['content'] = trim(XUtils::strimwidthWithTag($comment['content'], 0 ,100));
    echo <<<HTML
        <li>
            <img alt="{$comment['nickname']}" title="{$comment['nickname']}" src="{$comment['avatar']}" class="avatar img-thumbnail" height="40" width="40">
            <a href="{$comment['post_url']}#comment-{$comment['id']}" title="查看详情">
                <strong>{$comment['nickname']}：</strong>
            </a>
            <span title="发表在：{$comment['title']}">{$comment['content']}</span>
        </li>
HTML;
}
?>
    </ul>
</div>
<?php endif;?>