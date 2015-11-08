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
$author = $comment->url ? Html::a(Html::encode($comment->nickname), $comment->url, ['target' => '_blank', 'rel' => 'nofollow']) : $comment->nickname;
$replyTo = '';
if ($comment->type == Comment::TYPE_REPLYTO) {
    $replyTo = "<span class=\"replyTarget\">回复 <a href=\"#comment-{$comment->replyto}\"><em>{$comment->target_name}</em></a> : </span>";
}
$time = date('Y-m-d H:i', $comment->create_time);

echo <<<COMMENT
    <li class="comment {$comment->type}" id="comment-{$comment->id}">
        <div class="c-avatar">
            <img class="avatar" height="40" width="40" src="{$avatar}">
        </div>
        <div class="c-main">
            {$replyTo}{$content}
            <div class="c-meta">
                <span class="c-author">{$author}</span>
                <time>{$time}</time>
                <a class="comment-reply-link" href="javascript:void(0)" onclick="moveCommentForm({$comment->id})">回复</a>
            </div>
        </div>
    </li>
COMMENT;
