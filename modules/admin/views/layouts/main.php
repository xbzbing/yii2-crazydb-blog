<?php
use app\modules\admin\assets\AdminAsset;
use app\modules\admin\assets\AdminlteAsset;
use app\modules\admin\assets\FontAwesomeAsset;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

/**
 * @var \yii\web\View $this
 * @var string $content
 */
$adminlte = AdminlteAsset::register($this);
$fontAwesome = FontAwesomeAsset::register($this);
AdminAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?=Yii::$app->language?>">
<head>
	<meta charset="<?=Yii::$app->charset?>"/>
    <?= Html::csrfMetaTags() ?>
	<title><?=$this->title?></title>
	<?php $this->head() ?>
</head>
<body class="skin-blue">
	<?php $this->beginBody() ?>
    <div class="wrapper">
        <!--header-->
        <header class="main-header">
            <a href="javascript:void(0)" class="logo"><b><?=Yii::$app->name?></b></a>
            <nav class="navbar navbar-static-top" role="navigation">
                <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                    <span class="sr-only">Toggle navigation</span>
                </a>
                <div class="navbar-custom-menu">
                    <?=$this->render('navbar-menu')?>
                </div>
            </nav>
        </header>
        <!--/header-->
        <!--sidebar-->
        <aside class="main-sidebar">
            <section class="sidebar">
                <!-- Sidebar user panel -->
                <div class="user-panel">
                    <div class="pull-left info">
                        <p>
                            <strong>这里是主边栏显示区</strong>
                        </p>
                        <a>
                            <i class="fa fa-circle text-success"></i> <?= Yii::t('app', 'Online') ?>
                        </a>
                    </div>
                </div>
                <?= $this->render('sidebar-left') ?>
            </section>
        </aside>
        <!--/sidebar-->
        <!--content-->
        <div class="content-wrapper">
            <!--breadcrumb-->
            <section class="content-header">
                <?=Breadcrumbs::widget([
                    'tag' => 'ol',
                    'encodeLabels'=>false,
                    'homeLink' => ['label' => '<i class="fa fa-dashboard"> </i>'.Yii::t('app','Dashboard'), 'url' => ['/admin/default/index']],
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [$this->title],
                ]) ?>
            </section>
            <!--/breadcrumb-->
            <!-- Main content -->
            <section class="content">
                <?=$content?>
            </section>
        </div>
        <!--/content-->
        <!--footer-->
        <footer class="main-footer">
            <div class="pull-right hidden-xs">
                <b>Version</b> <?=Yii::$app->version?>
            </div>
            <strong>Copyright &copy; 2014-<?=date('Y')?> <a href="http://www.crazydb.com">Crazy Dabing</a>.</strong> All rights reserved.
        </footer>
        <!--/footer-->
    </div>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>