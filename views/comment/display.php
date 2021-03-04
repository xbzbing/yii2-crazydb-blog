<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 * 文章页面显示评论部分
 */
use yii\web\View;
use yii\helpers\Html;
use yii\helpers\Url;
use app\components\XUtils;
use app\components\CMSUtils;
use app\models\Comment;

/**
 * @var Comment $comment
 * @var View $this
 * @var string $baseUrl
 */
if (!isset($baseUrl))
    $baseUrl = Url::base().'/static';

$smilies = CMSUtils::getSmilies();
$content = $comment->content;
foreach ($smilies as $smilie)
    $content = str_replace(":{$smilie}:", "<img src=\"{$baseUrl}/images/smilie/icon_{$smilie}.gif\" alt=\"$smilie\" width=\"18px\" height=\"16px\"/>", $content);

$avatar = XUtils::getAvatar($comment->email, 40);
$display_name = Html::encode($comment->nickname);
if ($comment->isReply()){
    $replyTo = "<span class=\"replyTarget\">回复 <a href=\"#comment-{$comment->reply_to}\"><em>{$comment->target_name}</em></a> : </span>";
    $type = ' reply-to';
}else{
    $replyTo = '';
    $type = '';
}

$time = date('Y-m-d H:i', $comment->create_time);


echo <<<COMMENT
<div class="comment-panel{$type}" id="comment-{$comment->id}">
    <div class="avatar">
        <img class="img-thumbnail" src="{$avatar}" width="40" alt="{$comment->nickname}"/>
    </div>
    <div class="comment-body">
        <div class="comment-meta">
            <span class="name">
              {$display_name}
            </span>
            <span class="time">
                 <span class="glyphicon glyphicon-time"> </span> {$time}
            </span>
            <a class="comment-reply-link" href="javascript:void(0)" onclick="moveCommentForm({$comment->id})">回复</a>
        </div>
        <div class="comment-content">
            {$replyTo} {$content}
        </div>
    </div>
</div>
COMMENT;
