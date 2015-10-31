<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-5-24 下午3:19
 */

use yii\web\View;

/* @var View $this
 * @var $captcha string
 * @var $pwd string
 * @var $info string
 * @var $url string
 */
$captcha = isset($captcha) ? $captcha : 'hide-captcha';
$pwd = isset($pwd) ? $pwd : 'hide-pwd';
$info = isset($info) ? $info : '';
$url = isset($url) ? $url : '#';
?>
<form action="<?= $url; ?>" method="post">
    <div class="hidden-post">
        <div class="input-group">
            <p>本篇日志为隐藏状态，需要密码才能查看。</p>
            <?php
            if ($info)
                echo '<p><span class="label label-danger">', $info, '</span></p>';
            ?>
        </div>
        <div class="input-group">
        <span class="input-group-addon ccaptcha">
        <?php $this->widget('CCaptcha',
            array(
                'showRefreshButton' => true,
                'clickableImage' => true,
                'buttonType' => 'link',
                'buttonLabel' => '',
                'imageOptions' => array('alt' => '点击换图', 'align' => 'absmiddle', 'title' => '点击换图', 'height' => '32')
            )); ?>
        </span>
            <input type="text" class="form-control" name="<?= $captcha; ?>" placeholder="验证码">
            <span class="input-group-addon">密  码</span>
            <input type="password" class="form-control" name="<?= $pwd; ?>" placeholder="密码">
        <span class="input-group-btn">
              <button class="btn btn-primary" type="submit">提交</button>
        </span>
        </div>
    </div>
</form>
