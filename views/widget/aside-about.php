<?php
/**
 * @author xbzbing <xbzbing@gmail.com>
 * @link http://www.crazydb.com
 */
use yii\web\View;

/* @var View $this */
$js = <<<JS
       $("#about-me").popover({trigger:"hover",placement:"left",html:true,content:function(){return $("#about-lina").html()}});
JS;
$this->registerJs($js);
?>
<div class="widget aside-about" id="about-me">
    <h3 class="widget_title with-shadow"><i class="glyphicon glyphicon-user"></i>关于我</h3>
    <ul class="with-shadow">
        <li>
            <h4><a href="http://weibo.com/xbzbing" target="_blank" title="新浪微博：@疯狂的dabing">@疯狂的dabing</a></h4>

            <p>曾经是爱好网络安全的程序猿<br/>现在是爱好编程的安全攻城狮</p>
            <address>xbzbing#gmail.com</address>
        </li>
    </ul>
    <div id="about-lina" class="hidden">
        <p>解放天空的禁令</p>

        <p>冰冷的黑色虚无之刃</p>

        <p>与我的力量我的身体相结合</p>

        <p>共同踏上毁灭之途</p>

        <p>连众神的魂魄也将被击碎！</p>
    </div>
</div>