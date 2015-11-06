<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\web\View;
use app\assets\AppAsset;
use app\models\User;
/**
 * @var View $this
 * @var string $site_name
 * @var User $current_user
 * @var AppAsset $asset
 */
$current_user = Yii::$app->user->identity;
$menu_items = app\models\Nav::getNavTree();
if(Yii::$app->user->isGuest){
    $menu_items[] = [
        'label' => '用户',
        'items' => [
            ['label' => '登录', 'url' => ['site/login']],
            ['label' => '注册', 'url' => ['site/register']],
        ]
    ];
}else{
    $menu_items[] = [
        'label' => $current_user->nickname,
        'items' => [
            ['label' => '管理后台','url' => ['admin/'], 'visible' => $current_user->isAdmin()],
            '<li><a href="#" title="退出" id="logout-btn">退出</a>'
            . Html::beginForm(['/site/logout'], 'post', ['id' => 'logout-form'])
            . Html::endForm()
            . '</li>'
        ],
    ];
    $this->registerJs('$("#logout-btn").click(function(){ $("#logout-form").submit();});');
}
?>
<header class="index-header">
    <nav class="container" role="navigation">
        <div class="col-md-4 index-logo">
            <?= Html::img("{$asset->baseUrl}/images/site-logo.jpg", ['class' => 'img-circle']) ?>
        </div>
        <div class="navbar navbar-default col-md-6 index-navbar">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#site-navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?= Yii::$app->homeUrl ?>"><?= $site_name ?></a>
            </div>
            <div class="navbar-collapse collapse" id="site-navbar">
                <?= Nav::widget([
                    'options' => ['class' => 'nav navbar-nav'],
                    'items' => $menu_items,
                ]);
                ?>
            </div>
        </div>
    </nav>
</header>
