<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 * @see \\comment\display
 */

use yii\web\View;
/* @var View $this
 * @var $comments array
 * @var $pid integer
 */

//重排数组，以 cid 为键值
$temp = array();
if(count($comments)>0){
    foreach($comments as $key => $comment){
        $temp[$comment->id] = $comment;
    }
    //
    $comments = array();
    foreach($temp as $comment){
        $comment->targetName = isset($temp[$comment->replyto])?$temp[$comment->replyto]->author:'';
        //这里要找到真正的父ID
        $parentId = $comment->replyto;
        if($parentId > 0){
            $parent = $temp[$parentId];
            while($parent->replyto>0){
                $parent = $temp[$parent->replyto];
            }
            $parentId = $parent->id;
        }
        //按id重组留言
        $comments[$parentId][] = $comment;
    }
    unset($parent,$temp,$parentId,$comment);
}
?>
<div class="post-comments">
    <form method="post" name="comment_content" action="#">
    <h3 id="comments">
        留言交流
    </h3>
    <ol class="comment-list">
<?php
if(count($comments)>0){
    foreach($comments[0] as $parent){
        $this->renderPartial("//comment/display",array('comment'=>$parent));
        //没有回复
        if(isset($comments[$parent->id])){
            //多级评论
            foreach($comments[$parent->id] as $comment){
                $this->renderPartial("//comment/display",array('comment'=>$comment));
            }
        }
    }
}else{
    echo '<div id="no-comment">没有评论</div>';
}
?>
    </ol>

<?php
if($this->params['allow_comment']=='open'):
?>
    <div id="comment-area">
        <div class="comment" id="comment">
            <div id="comment_error" class="alert alert-danger">
            </div>
            <div class="leave-comment">
                <div class="col-md-4">
                    <?php if(Yii::app()->user->isGuest):?>
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
                        <input type="text" maxlength="80" data-title="网站地址" class="form-control" id="Comment_url" name="Comment[url]" placeholder="WebSite URL">
                    </div>
                    <div class="input-group">
                    <span class="input-group-addon ccaptcha">
                        <?php $this->widget('CCaptcha',
                            array (
                            'showRefreshButton' => true,
                            'clickableImage' => true,
                            'buttonType' => 'link',
                            'buttonLabel' => '<em class="glyphicon glyphicon-refresh" title="点击换图"></em>',
                            'imageOptions' => array ('alt' => '点击换图', 'align'=>'absmiddle','title'=>'点击换图' , 'height'=>'32')
                            ) );?>
                    </span>
                        <input type="text" required="required" data-title="验证码" class="form-control" name="Comment[captcha]" placeholder="* Captcha">
                    </div>
                    <?php else:?>
                    <div class="col-md-6 comment-author">
                        <div class="thumbnail">
                            <img src="<?php echo XUtils::getAvatar(Yii::app()->user->email,100);?>" width="100" height="100">
                        </div>
                        <strong><?php echo Yii::app()->user->nickname;?></strong>
                    </div>
                    <div class="comment-captcha col-md-6">
                        <span class="ccaptcha input-group">
                            <?php $this->widget('CCaptcha',
                                array (
                                'showRefreshButton' => true,
                                'clickableImage' => true,
                                'buttonType' => 'link',
                                'buttonLabel' => '<span><em class="glyphicon glyphicon-refresh" title="点击换图"></em></span>',
                                'imageOptions' => array ('alt' => '点击换图', 'align'=>'absmiddle','title'=>'点击换图' , 'height'=>'32')
                                ) );?>
                        </span>
                        <input type="text" required="required" class="form-control" data-title="验证码" name="Comment[captcha]" placeholder="* Captcha">
                    </div>
                    <?php endif;?>

                </div>
                <div class="col-md-8">
                    <div class="comment-smilie input-group">
                        <span class="smilie-img">
                        <?php
                        //尽量不打乱原有顺序
                        $random_smilie = $this->smilie[array_rand($this->smilie)];
                        echo '<img data-type="comment-smilie" data-smilie="'.$random_smilie.'" src="'.Yii::app()->baseUrl.'/static/images/smilie/icon_'.$random_smilie.'.gif" width="18px" height="16px"/>';
                        foreach($this->smilie as $smilie){
                            if($random_smilie==$smilie)
                                continue;
                            echo '<img data-type="comment-smilie" data-smilie="'.$smilie.'" src="'.Yii::app()->baseUrl.'/static/images/smilie/icon_'.$random_smilie.'.gif" width="18px" height="16px"/>';
                        }
                        ?>
                        </span>
                        <span id="more_smilie" title="更多表情"><em class="glyphicon glyphicon-chevron-right"></em></span>
                    </div>
                    <input type="hidden" name="Comment[replyto]" value="0" id="parentId"/>
                    <input type="hidden" name="inajax" value="1"/>
                    <textarea class="form-control" data-title="留言内容" required="required" name="Comment[content]" id="leave-a-comment" placeholder="留下你的看法，欢迎交流 :)"></textarea>
                    <div class="actionPanel">
                        <?php if(Yii::app()->params['send_mail_on_comment']=='open' && isset(Yii::app()->mailer)):?>
                        <div class="checkbox pull-left">
                            <label>
                                <input name="Comment[sendMail]" value="1" type="checkbox"> 邮件通知对方
                            </label>
                        </div>
                        <?php endif;?>
                        <button id="sendComment" class="btn btn-primary">
                            提交留言 <em class="glyphicon glyphicon-send"></em>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br class="clearfix">
<?php
$comment_url = $this->createUrl('post/comment',array('id'=>$pid));
$baseUrl = Yii::app()->baseUrl;
$script = <<<SCRIPT
    var smile_path = "{$baseUrl}/static/images/smilie/icon_";
    var comment_submit = "{$comment_url}";
    var postId = "{$pid}";
    var changeCaptcha = false;
SCRIPT;
$this->clientScript->registerScript('comment',$script,CClientScript::POS_HEAD);
$this->clientScript->registerScriptFile($this->assetsUrl.'/js/comment.js',CClientScript::POS_END);
else:
$this->clientScript->registerScript('comment-reply-hide','$(".comment-reply-link").hide();',CClientScript::POS_READY);
endif;
?>
    </form>
</div>