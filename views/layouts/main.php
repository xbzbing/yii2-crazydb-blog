<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Nav;
use app\assets\AppAsset;
use yii\web\View;
/**
 * @var View $this
 * @var string $content
 * @var array $site_params
 */
$asset = AppAsset::register($this);
$menu_items = app\models\Nav::getNavTree(1);
if(Yii::$app->user->isGuest){
    $menu_items[] = ['label' => 'Login', 'url' => ['site/login']];
}else{
    $menu_items[] = ['label' => Yii::$app->user->identity->nickname, 'items' => [
            ['label' => 'Admin Page','url' => ['admin/']],
            ['label' => 'Logout','url' => ['site/logout']],
        ],
    ];
}
$site_name = ArrayHelper::getValue(Yii::$app->params, 'site_name', Yii::$app->name);
$this->beginPage(); ?>
<!DOCTYPE html>
<html lang="<?=Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
    <header class="index-header">
        <nav class="container" role="navigation">
            <div class="col-md-4 index-logo">
                <?= Html::img("{$asset->baseUrl}/images/site-logo.jpg", ['class' => 'img-circle']) ?>
            </div>
            <div class="navbar navbar-default col-md-6 index-navbar">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="site-navbar">
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
    <?= $content ?>
    <?= $this->render('footer') ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>