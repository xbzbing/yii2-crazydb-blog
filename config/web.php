<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'timeZone'=>'PRC',
    'language' => 'zh-CN',
    'bootstrap' => ['log'],
    'name'=>'X-CMS',
    'modules'=>[
        'admin'=>[
            'class'=>'app\modules\admin\Module'
        ]
    ],
    'components' => [
        'request'=>[
            'cookieValidationKey'=>'cookieValidateKey!'
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'jsOptions'=>['position'=>yii\web\View::POS_HEAD]
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules'=>[
                '<controller:(post|comment)>/<id:\d+>/<action:(create|update|delete)>' => '<controller>/<action>',
                '<controller:(post|comment)>/<id:\d+>' => '<controller>/read',
                '<controller:(post|comment)>s' => '<controller>/list',
                'catalog/<name>'=>'category/alias',
                'archive/<name>'=>'post/alias'
            ]
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__.'/db.php'),
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';
}

return $config;
