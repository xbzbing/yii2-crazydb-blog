<?php
use app\modules\admin\Module;
use app\modules\admin\assets\AdminAsset;
use app\modules\admin\assets\AdminlteAsset;
use app\modules\admin\assets\FontAwesomeAsset;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

/**
 * @var \yii\web\View $this
 * @var string $content
 * @var Module $current_module
 */
AdminlteAsset::register($this);
FontAwesomeAsset::register($this);
AdminAsset::register($this);
$current_module = Yii::$app->controller->module;
$message = Yii::$app->session->getFlash('admin');
if ($message) {
    try{
        $this->registerJs("
    Messenger().post({
        message: '{$message['detail']}',
        type: '{$message['status']}',
        showCloseButton: true
    });
    ");
    }catch (Exception $e){}
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?= Html::csrfMetaTags() ?>
    <title><?= $this->title ? $this->title . ' | ' . $current_module->name : $current_module->name ?></title>
    <?php $this->head() ?>
</head>
<body class="skin-blue sidebar-mini fixed">
<?php $this->beginBody() ?>
<div class="wrapper">
    <header class="main-header">
        <a href="javascript:void(0)" class="logo"><span class="logo-mini"><b>DB</b></span><span class="log-lg"><b><?= Yii::$app->name ?></b></span></a>
        <nav class="navbar navbar-static-top" role="navigation">
            <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                <span class="sr-only">Toggle navigation</span>
            </a>
            <div class="navbar-custom-menu">
                <?= $this->render('navbar-menu') ?>
            </div>
        </nav>
    </header>
    <?= $this->render('sidebar-left') ?>
    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                <?= $this->title ?>
            </h1>
            <?= Breadcrumbs::widget([
                'tag' => 'ol',
                'encodeLabels' => false,
                'homeLink' => ['label' => '<i class="fa fa-dashboard"> </i>控制台', 'url' => ['/manager']],
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [$this->title],
            ]) ?>
        </section>
        <section class="content">
            <?= $content ?>
        </section>
    </div>
    <footer class="main-footer">
        <div class="pull-right hidden-xs">
            <b>Version</b> <?= $current_module->version ?>
        </div>
        <strong>Copyright &copy; 2014-<?= date('Y') ?> <a href="http://www.crazydb.com">疯狂数据</a>.</strong>
    </footer>
</div>
<?= $this->render('sidebar-right') ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>