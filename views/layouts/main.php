<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\web\View;
use app\assets\AppAsset;
use app\models\Option;
/**
 * @var View $this
 * @var string $content
 * @var array $site_params
 */
$asset = AppAsset::register($this);
$site_name = ArrayHelper::getValue(Yii::$app->params, 'site_name', Yii::$app->name);
if(!empty($this->params[Option::SEO_KEYWORDS]))
    $this->registerMetaTag(['name' => 'keywords', 'content' => $this->params[Option::SEO_KEYWORDS]]);
if(!empty($this->params[Option::SEO_DESCRIPTION]))
    $this->registerMetaTag(['name' => 'description', 'content' => $this->params[Option::SEO_DESCRIPTION]]);
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
<div class="site-container">
    <?= $this->render('header', ['asset' => $asset, 'site_name' => $site_name]) ?>
    <?= $content ?>
    <?= $this->render('footer') ?>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>