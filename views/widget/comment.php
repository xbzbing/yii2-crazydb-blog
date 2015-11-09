<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 * @see \\comment\display
 */

use yii\captcha\Captcha;
use yii\web\View;
use yii\helpers\Html;
use app\assets\AppAsset;
use app\components\CMSUtils;
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
$smilies = CMSUtils::getSmilies();
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
        $comment->target_name = isset($temp[$comment->reply_to]) ? $temp[$comment->reply_to]->nickname : '';
        //这里要找到真正的父ID
        $parentId = $comment->reply_to;
        if ($parentId > 0) {
            $parent = $temp[$parentId];
            while ($parent->reply_to > 0) {
                $parent = $temp[$parent->reply_to];
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
    <h3 id="comments">
        留言交流
    </h3>
    <div class="comment-list">
        <?php
        if (count($comments) > 0) {
            foreach ($comments[0] as $parent) {
                echo $this->render("//comment/display", ['comment' => $parent, 'baseUrl' => $asset->baseUrl]);
                //没有回复
                if (isset($comments[$parent->id])) {
                    //多级评论
                    foreach ($comments[$parent->id] as $comment) {
                        echo $this->render("//comment/display", ['comment' => $comment, 'baseUrl' => $asset->baseUrl]);
                    }
                }
            }
        }else{
            echo '<div id="no-comment">没有评论</div>';
        }
        ?>
    </div>
<?php
if(CMSUtils::getSysConfig(Option::ALLOW_COMMENT) === Option::STATUS_OPEN):
?>
    <div id="comment-area">
        <div class="comment" id="comment">
            <?= Html::beginForm(['/comment/add', 'id' => $pid], 'post', ['id' => 'add-comment']) ?>
            <div id="comment-error" class="alert alert-danger"></div>
            <div class="leave-comment">
                <div class="col-md-4">
                    <?php if(Yii::$app->user->isGuest):?>
                        <div class="input-group">
                            <span class="input-group-addon"><em class="glyphicon glyphicon-user"></em></span>
                            <input type="text" required="required" tabindex="1" data-title="姓名" class="form-control" name="Comment[nickname]" placeholder="* Name">
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon"><em class="glyphicon glyphicon-envelope"></em></span>
                            <input type="text" required="required" tabindex="2" data-title="邮箱" class="form-control" name="Comment[email]" placeholder="* E-mail">
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon"><em class="glyphicon glyphicon-globe"></em></span>
                            <input type="text" maxlength="80" tabindex="3" data-title="网站地址" class="form-control" id="comment-url" name="Comment[url]" placeholder="WebSite URL">
                        </div>
                        <?= Captcha::widget([
                            'name' => 'Comment[captcha]',
                            'template' => '<div class="input-group"><span class="input-group-addon captcha-cover">{image}</span>{input}</div>',
                            'options' => ['class' => 'form-control', 'tabindex' => 4],
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
                            'name' => 'Comment[captcha]',
                            'template' => '<div class="comment-captcha col-md-6"><span class="captcha">{image}</span>{input}</div>',
                            'options' => ['class' => 'form-control', 'tabindex' => 4],
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
                    <input type="hidden" name="Comment[reply_to]" value="0" id="parentId"/>
                    <textarea class="form-control" data-title="留言内容" required="required" name="Comment[content]" id="comment-content" tabindex="5" placeholder="留下你的看法，欢迎交流 :)"></textarea>
                    <div class="actionPanel">
                        <?php if (CMSUtils::getSysConfig(Option::SEND_MAIL_ON_COMMENT) === Option::STATUS_OPEN && Yii::$app->get('mailer', false)): ?>
                            <div class="checkbox pull-left">
                                <label>
                                    <input name="Comment[sendMail]" value="1" type="checkbox" tabindex="6"> 邮件通知对方
                                </label>
                            </div>
                        <?php endif;?>
                        <button id="sendComment" class="btn btn-primary" tabindex="7" type="button">
                            提交留言 <em class="glyphicon glyphicon-send"></em>
                        </button>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>
    <br class="clearfix">
<?php
    $script = <<<SCRIPT
    var smile_path = "{$baseUrl}/images/smilie/icon_";
SCRIPT;
    $this->registerJs($script, View::POS_HEAD);
    $this->registerJsFile("{$baseUrl}/js/comment.min.js");
else:
    $this->registerJs('$(".comment-reply-link").hide();');
endif;
?>
</div>