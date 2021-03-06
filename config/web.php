<?php

$config = [
    'id' => 'crazydb',
    'basePath' => dirname(__DIR__),
    'timeZone' => 'PRC',
    'language' => 'zh-CN',
    'bootstrap' => ['log'],
    'name' => 'Crazydb-Blog',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'modules' => [
        'admin' => [
            'class' => 'app\modules\admin\Module'
        ],
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'cookieValidateKey!'
        ],
        'cache' => require(__DIR__ . '/cache.php'),
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'jsOptions' => ['position' => 1]
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'page/<page:\d+>' => 'site/index',
                '' => 'site/index',//首页
                'login' => 'site/login',
                'logout' => 'site/logout',
                'register' => 'site/register',
                'catalog/<name>/page/<page:\d+>' => 'category/show', //SEO URL优化
                'tag/<name>/page/<page:\d+>' => 'tag/show',
                'user/<name>/page/<page:\d+>' => 'user/show',
                'posts/page/<page:\d+>' => 'post/list',
                'catalog/<name>' => 'category/show',//分类
                'archive/<name>' => 'post/show',//文章
                'tag/<name>' => 'tag/show',//标签
                'user/profile/<name>' => 'user/show',//用户
                'archives/<year:(\d{4})>/<month:(\d{1,2})>' => 'post/archives-date',
                'archives' => 'post/archives',
                '<controller:(comment)>/add/<id:\d+>' => 'comment/add',
                '<controller>/show/<name:\w+>' => '<controller>/show',
                '<controller:(post|comment)>/<id:\d+>' => '<controller>/view',
                '<controller:(post|comment|tag)>s' => '<controller>/list',
                '<controller:(post|comment|tag)>s/page/<page:\d+>' => '<controller>/list',
            ]
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => require(__DIR__ . '/mail.php'),
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
    ],
    'params' => require(__DIR__ . '/params.php'),
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';
}

return $config;
