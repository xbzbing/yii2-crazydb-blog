<?php
/* @var $this Controller */
$menu = array(
    array('label'=>'首页','url'=>array('site/index')),
    array('label'=>'工具','url'=>array('xsser/')),
);
if(Yii::app()->user->isGuest){
    array_push($menu,
        array('label'=>'用户 <b class="caret"></b>',
              'url'=>'#',
              'items'=>array(
                  array('label'=>'登录', 'url'=>array('/site/login')),
                  array('label'=>'注册', 'url'=>array('/user/register'))
              ),
              'submenuOptions'=>array('class'=>'dropdown-menu'),
              'linkOptions'=>array('class'=>'dropdown-toggle','data-toggle'=>'dropdown'),
              'itemOptions'=>array('class'=>'dropdown'),
    ));
}else{
    array_push($menu,
        array('label'=>'<strong>'.Yii::app()->user->nickname.'</strong> <b class="caret"></b>',
              'url'=>'#',
              'items'=>array(
                  array('label'=>'个人资料','url'=>array('user/'.Yii::app()->user->nickname)),
                  array('label'=>'修改密码','url'=>array('user/modifyPwd')),
                  array('label'=>'管理后台','url'=>array('admin/')),
                  array('label'=>'','url'=>'#','itemOptions'=>array('class'=>'divider')),
                  array('label'=>'退出','url'=>array('site/logout')),
              ),
              'submenuOptions'=>array('class'=>'dropdown-menu'),
              'linkOptions'=>array('class'=>'dropdown-toggle','data-toggle'=>'dropdown'),
              'itemOptions'=>array('class'=>'dropdown'),
    ));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="renderer" content="webkit">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?php echo $this->pageTitle;?></title>
<meta name="author" content="xbzbing" />
<meta name="keywords" content="<?php echo $this->seoKeywords;?>">
<meta name="description" content="<?php echo $this->seoDescription;?>">
<link rel="stylesheet" type="text/css" href="<?php echo $this->cssUrl; ?>/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->cssUrl; ?>/ext.min.css" />
<?php Yii::app()->clientScript->registerCoreScript('jquery'); ?>
<!--[if lt IE 9]>
<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>
	<header class="index-header">
		<nav class="container" role="navigation">
			<div class="col-md-4 index-logo">
				<img src="<?php echo $this->imgUrl; ?>/xbzbing.jpg" class="img-circle" />
			</div>
			<div class="navbar navbar-default col-md-6 index-navbar">
				<div class="navbar-header">
					<div class="navbar-header">
				    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#site-navbar">
				      <span class="sr-only">Toggle navigation</span>
				      <span class="icon-bar"></span>
				      <span class="icon-bar"></span>
				      <span class="icon-bar"></span>
				    </button>
				    <span class="navbar-brand"><?php echo Yii::app()->params['site_name'];?></span>
				</div>
				</div>
				<div class="navbar-collapse collapse" id="site-navbar">
                    <?php $this->widget('zii.widgets.CMenu',array('items'=>$menu,'htmlOptions'=>array('class'=>'nav navbar-nav'),'encodeLabel'=>false)); ?>
				</div>
			</div>
		</nav>
	</header>
	<?php echo $content; ?>
<!--
    <footer id="footer">
        <div class="container">
            暂时没有小伙伴的连接 so 就隐藏了
        </div>
	</footer>
-->
    <footer id="copyright">
        <?php
        echo Yii::app()->params['site_name'],' ',Yii::app()->params['copyright'],' ',Yii::app()->params['site_icp'];
        ?>
        <?php if(Yii::app()->params['site_analyzer']):?>
        <div role="scriptAnalyzer" class="scriptAnalyzer">
            <?php echo Yii::app()->params['site_analyzer'];?>
        </div>
        <?php endif;?>
    </footer>
	<script src="<?php echo $this->jsUrl; ?>/bootstrap.min.js"></script>
</body>
</html>
