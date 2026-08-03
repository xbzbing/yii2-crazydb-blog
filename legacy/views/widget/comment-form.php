<?php
use yii\web\View;

/* @var View $this
 * @var int $pid
 */
?>
<div class="comment" id="comment">
    <div class="leave-comment">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-addon"><em class="glyphicon glyphicon-user"></em></span>
                <input type="text" class="form-control" name="author" placeholder="* Name">
            </div>
            <div class="input-group">
                <span class="input-group-addon"><em class="glyphicon glyphicon-envelope"></em></span>
                <input type="text" class="form-control" name="email" placeholder="* E-mail">
            </div>
            <div class="input-group">
                <span class="input-group-addon"><em class="glyphicon glyphicon-globe"></em></span>
                <input type="text" class="form-control" name="website" placeholder="WebSite URL">
            </div>
            <div class="input-group">
                <span class="input-group-addon ccaptcha">
                    <?php $this->widget('CCaptcha',
                        array(
                            'showRefreshButton' => true,
                            'clickableImage' => true,
                            'buttonType' => 'link',
                            'buttonLabel' => '<em class="glyphicon glyphicon-refresh" title="点击换图"></em>',
                            'imageOptions' => array('alt' => '点击换图', 'align' => 'absmiddle', 'title' => '点击换图', 'height' => '32')
                        )); ?>
                </span>
                <input type="text" class="form-control" name="captcha" placeholder="Captcha">
            </div>

        </div>
        <div class="col-md-8">
            <div class="comment-smilie input-group">
                <span class="smilie-img">
                <?php
                //尽量不打乱原有顺序
                $random_smilie = $this->smilie[array_rand($this->smilie)];
                echo '<img data-type="comment-smilie" data-smilie="' . $random_smilie . '" src="' . Yii::app()->baseUrl . '/static/images/smilie/icon_' . $random_smilie . '.gif" width="18px" height="16px"/>';
                foreach ($this->smilie as $smilie) {
                    if ($random_smilie == $smilie)
                        continue;
                    echo '<img data-type="comment-smilie" data-smilie="' . $smilie . '" src="' . Yii::app()->baseUrl . '/static/images/smilie/icon_' . $random_smilie . '.gif" width="18px" height="16px"/>';
                }
                ?>
                </span>
                <span id="more_smilie" title="更多表情"><em class="glyphicon glyphicon-chevron-right"></em></span>
            </div>
            <textarea class="form-control" name="comment-content" id="leave-a-comment"
                      placeholder="留下你的看法，欢迎交流 :)"></textarea>
            <span class="input-group-addon">
                <span class="pull-left">* 邮箱和昵称一定要填写，放心吧，不会泄漏你的邮箱的 :)</span>
                <button onclick="leaveComment(<?php echo $pid; ?>,0)" class="btn btn-primary pull-right">
                    提交留言 <em class="glyphicon glyphicon-send"></em>
                </button>
            </span>
        </div>
    </div>
</div>

<script>
    var smile_path = "<?php echo Yii::app()->baseUrl,'/static/images/smilie/icon_';?>";
    var comment_submit = "<?php $this->createUrl('post/comment',array('id'=>$pid))?>";
    function leaveComment(post, pid) {
        alert(post + pid);
    }
    $(function () {
        $("#more_smilie").click(function () {
            $(this).remove();
            $("img[data-type=comment-smilie]").each(function () {
                $(this).attr("src", smile_path + $(this).attr("data-smilie") + ".gif");
            });
            $(".smilie-img").animate({width: "100%"});
        });
        $("img[data-type=comment-smilie]").click(function () {
            var myField;
            var tag = $(this).attr("data-smilie");
            tag = ' [' + tag + '] ';
            if (document.getElementById('leave-a-comment') && document.getElementById('leave-a-comment').type == 'textarea') {
                myField = document.getElementById('leave-a-comment');
            } else {
                return false;
            }
            if (document.selection) {
                myField.focus();
                sel = document.selection.createRange();
                sel.text = tag;
                myField.focus();
            }
            else if (myField.selectionStart || myField.selectionStart == '0') {
                var startPos = myField.selectionStart;
                var endPos = myField.selectionEnd;
                var cursorPos = endPos;
                myField.value = myField.value.substring(0, startPos)
                    + tag
                    + myField.value.substring(endPos, myField.value.length);
                cursorPos += tag.length;
                myField.focus();
                myField.selectionStart = cursorPos;
                myField.selectionEnd = cursorPos;
            }
            else {
                myField.value += tag;
                myField.focus();
            }
        });
    });
</script>