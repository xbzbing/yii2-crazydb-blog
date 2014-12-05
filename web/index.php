<?php

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('crazydb',dirname(__DIR__).'/extension/crazydb');
Yii::setAlias('@crazydb/ueditor',dirname(__DIR__).'/extension/crazydb/yii2-ueditor-ext');

$config = require(__DIR__ . '/../config/web.php');

(new yii\web\Application($config))->run();