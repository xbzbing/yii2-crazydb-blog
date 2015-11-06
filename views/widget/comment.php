<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 * @see \\comment\display
 */

use yii\captcha\Captcha;
use yii\web\View;
use yii\helpers\Url;
use app\assets\AppAsset;
use app\models\Comment;
use app\models\Option;
use app\models\User;
use app\components\XUtils;

/* @var View $this
 * @var Comment[] $comments
 * @var integer $pid
 * @var AppAsset $asset
 * @var User $current_user;
 * @var Comment[] $temp
 */
$smilies = [
    'question',
    'razz',
    'sad',
    'evil',
    'exclaim',
    'smile',
    'redface',
    'biggrin',
    'surprised',
    'eek',
    'confused',
    'cool',
    'lol',
    'mad',
    'twisted',
    'rolleyes',
    'wink',
    'idea',
    'arrow',
    'neutral',
    'cry',
    'mrgreen'
];
$current_user = Yii::$app->user->identity;
if(empty($asset))
    $asset = AppAsset::register($this);
$baseUrl = $asset->baseUrl;
//重排数组，以 cid 为键值
$temp = [];
if (count($comments) > 0) {
    foreach ($comments as $key => $comment) {
        $temp[$comment->id] = $comment;
    }
    //
    $comments = array();
    foreach ($temp as $comment) {
        $comment->target_name = isset($temp[$comment->replyto]) ? $temp[$comment->replyto]->author : '';
        //这里要找到真正的父ID
        $parentId = $comment->replyto;
        if ($parentId > 0) {
            $parent = $temp[$parentId];
            while ($parent->replyto > 0) {
                $parent = $temp[$parent->replyto];
            }
            $parentId = $parent->id;
        }
        //按id重组留言
        $comments[$parentId][] = $comment;
    }
    unset($parent, $temp, $parentId, $comment);
}
?>
<div class="post-comments">
    <form method="post" name="comment_content" action="#">
    <h3 id="comments">
        留言交流
    </h3>
    <ol class="comment-list">
<?php
if (count($comments) > 0) {
    foreach ($comments[0] as $parent) {
        echo $this->render("//comment/display", ['comment' => $parent]);
        //没有回复
        if (isset($comments[$parent->id])) {
            //多级评论
            foreach ($comments[$parent->id] as $comment) {
                echo $this->render("//comment/display", ['comment' => $comment]);
            }
        }
    }
}else{
    echo '<div id="no-comment">没有评论</div>';
}
?>
    </ol>

<?php
if(isset(Yii::$app->params[Option::ALLOW_COMMENT]) && Yii::$app->params[Option::ALLOW_COMMENT] == Option::STATUS_OPEN):
?>
    <div id="comment-area">
        <div class="comment" id="comment">
            <div id="comment_error" class="alert alert-danger">
            </div>
            <div class="leave-comment">
                <div class="col-md-4">
                    <?php if(Yii::$app->user->isGuest):?>
                    <div class="input-group">
                        <span class="input-group-addon"><em class="glyphicon glyphicon-user"></em></span>
                        <input type="text" required="required" data-title="姓名" class="form-control" name="Comment[author]" placeholder="* Name">
                    </div>
                    <div class="input-group">
                        <span class="input-group-addon"><em class="glyphicon glyphicon-envelope"></em></span>
                        <input type="text" required="required" data-title="邮箱" class="form-control" name="Comment[email]" placeholder="* E-mail">
                    </div>
                    <div class="input-group">
                        <span class="input-group-addon"><em class="glyphicon glyphicon-globe"></em></span>
                        <input type="text" maxlength="80" data-title="网站地址" class="form-control" id="comment-url" name="Comment[url]" placeholder="WebSite URL">
                    </div>
                        <?= Captcha::widget([
                            'name' => 'captcha',
                            'template' => '<div class="input-group"><span class="input-group-addon"><em class="glyphicon glyphicon-ok-sign"></em></span>{input}<span class="input-group-addon captcha-cover">{image}</span></div>',
                            'options' => ['tabindex' => '3', 'class' => 'form-control'],
                            'imageOptions' => ['alt' => '点击换图', 'title' => '点击换图', 'style' => 'cursor:pointer', 'height' => '32']
                        ]); ?>
                    <?php else:?>
                    <div class="col-md-6 comment-author">
                        <div class="thumbnail">
                            <img src="<?= XUtils::getAvatar($current_user->email,100) ?>" width="100" height="100">
                        </div>
                        <strong><?= $current_user->nickname ?></strong>
                    </div>
                        <?= Captcha::widget([
                            'name' => 'captcha',
                            'template' => '<div class="comment-captcha col-md-6"><span class="captcha">{image}</span>{input}</div>',
                            'options' => ['tabindex' => '2', 'class' => 'form-control'],
                            'imageOptions' => ['alt' => '点击换图', 'title' => '点击换图', 'style' => 'cursor:pointer', 'height' => '32']
                        ]); ?>
                    <?php endif;?>

                </div>
                <div class="col-md-8">
                    <div class="comment-smilie input-group">
                        <span class="smilie-img">
                        <?php
                        //尽量不打乱原有顺序
                        $random_smilie = $smilies[array_rand($smilies)];
                        echo '<img data-type="comment-smilie" data-smilie="' . $random_smilie . '" src="' . $baseUrl . '/images/smilie/icon_' . $random_smilie . '.gif" width="18px" height="16px"/>';
                        foreach ($smilies as $smilie) {
                            if ($random_smilie == $smilie)
                                continue;
                            echo '<img data-type="comment-smilie" data-smilie="' . $smilie . '" src="' . $baseUrl . '/images/smilie/icon_' . $random_smilie . '.gif" width="18px" height="16px"/>';
                        }
                        ?>
                        </span>
                        <span id="more_smilie" title="更多表情"><em class="glyphicon glyphicon-chevron-right"></em></span>
                    </div>
                    <input type="hidden" name="Comment[replyto]" value="0" id="parentId"/>
                    <textarea class="form-control" data-title="留言内容" required="required" name="Comment[content]" id="leave-a-comment" tabindex="1" placeholder="留下你的看法，欢迎交流 :)"></textarea>
                    <div class="actionPanel">
                        <?php if (isset(Yii::$app->params[Option::SEND_MAIL_ON_COMMENT]) && Yii::$app->params[Option::SEND_MAIL_ON_COMMENT] == Option::STATUS_OPEN && isset(Yii::$app->mailer)): ?>
                        <div class="checkbox pull-left">
                            <label>
                                <input name="Comment[sendMail]" value="1" type="checkbox" tabindex="3"> 邮件通知对方
                            </label>
                        </div>
                        <?php endif;?>
                        <button id="sendComment" class="btn btn-primary" tabindex="4">
                            提交留言 <em class="glyphicon glyphicon-send"></em>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br class="clearfix">
<?php
    $comment_url = Url::to(['post/comment', 'id' => $pid]);
    $script = <<<SCRIPT
    var smile_path = "{$baseUrl}/images/smilie/icon_";
    var comment_submit = "{$comment_url}";
    var postId = "{$pid}";
    var changeCaptcha = false;
SCRIPT;
    $this->registerJs($script, View::POS_HEAD);
    $this->registerJsFile("{$baseUrl}/js/comment.js");
else:
    $this->registerJs('$(".comment-reply-link").hide();');
endif;
?>
    </form>
</div>