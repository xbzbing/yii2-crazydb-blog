<?php
use yii\helpers\Url;
use yii\web\View;
/**
 * @var View $this
 */
$current_url = Url::current();
$current_parent = Url::to([Yii::$app->controller->id.'/index']);
$nav_init = <<<SCRIPT
$("a[href='{$current_url}']").parent("li").addClass("active");
$("a[href='{$current_parent}']").parent("li").addClass("active");
SCRIPT;
$this->registerJs($nav_init, View::POS_READY,'sidebar_init');
?>
<aside class="main-sidebar">
    <section class="sidebar">
        <div class="user-panel">
            <div class="pull-left info">
                <p>
                    <strong>这里是主边栏显示区</strong>
                </p>
                <a>
                    <i class="fa fa-circle text-success"></i> Online                        </a>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="<?=Url::to(['default/index'])?>"><i class="fa fa-dashboard"> </i><?=Yii::t('app','Dashboard')?></a></li>
            <li><a href="<?=Url::to(['post/index'])?>"><?=Yii::t('app','Posts')?></a></li>
            <li><a href="<?=Url::to(['comment/index'])?>"><?=Yii::t('app','Comments')?></a></li>
            <li><a href="<?=Url::to(['category/index'])?>"><?=Yii::t('app','Category')?></a></li>
            <li><a href="<?=Url::to(['nav'])?>"><?=Yii::t('app','Nav')?></a>
            <ul class="treeview-menu">
                <li><a href="<?=Url::to(['nav/index'])?>"><?=Yii::t('app','Nav')?></a></li>
                <li><a href="<?=Url::to(['nav/tree'])?>"><?=Yii::t('app','Nav Tree')?></a></li>
            </ul>
            </li>
            <li class="treeview">
                <a href="<?=Url::to(['config/index'])?>">
                    <i class="fa fa-cogs"> </i><?=Yii::t('app','System Setting')?><i class="fa fa-angle-left pull-right"> </i>
                </a>
                <ul class="treeview-menu">
                    <li><a href="<?=Url::to(['config/setting'])?>"><?=Yii::t('app','Base Setting')?></a></li>
                    <li><a href="<?=Url::to(['config/seo'])?>"><?=Yii::t('app','SEO')?></a></li>
                    <li><a href="<?=Url::to(['config/slider'])?>"><?=Yii::t('app','Slider')?></a></li>
                    <li><a href="<?=Url::to(['config/cache'])?>"><?=Yii::t('app','Site Cache')?></a></li>
                </ul>
            </li>
        </ul>
    </section>
</aside>