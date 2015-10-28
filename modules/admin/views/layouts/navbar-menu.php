<?php
use yii\bootstrap\Nav;
use app\components\Common;
use app\models\User;
/* @var User $current_user */
$current_user = Yii::$app->user->identity;

$menuItems = [
    ['label' => '前台首页', 'url' => ['/site/index'], 'linkOptions' => ['target' => '_blank']],
];

switch (Common::getLanguage()) {
    case 'en':
        $languageLabel =' English';
        break;
    case 'zh-CN':
        $languageLabel = '简体中文';
        break;
    default:
        $languageLabel = 'English';
        break;
}

$menuItems[] = [
    'label' => $languageLabel,
    'url' => '#',
    'items' => [
        ['label' => ' English', 'url' => ['default/locale', 'language' => 'en']],
        ['label' => ' 简体中文', 'url' => ['default/locale', 'language' => 'zh-CN']],
    ]
];

$menuItems[] =  [
    'label' => $current_user->nickname,
    'url' => ['#'],
    'active' => false,
    'items' => [
        [
            'label' => '<i class="fa fa-user"></i> ' . '个人资料',
            'url' => ['/user'],
        ],
        [
            'label' => '<i class="fa fa-sign-out"></i> ' . '注销',
            'url' => ['/role'],
        ],
    ],
];

echo Nav::widget([
    'options' => ['class' => 'navbar-nav navbar-right'],
    'items' => $menuItems,
    'encodeLabels' => false
]);