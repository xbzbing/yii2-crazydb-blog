<?php
use yii\bootstrap\Nav;
use app\components\Common;

$menuItems = [
    ['label' => Yii::t('app', 'Frontend Home'), 'url' => ['/site/index'], 'linkOptions' => ['target' => '_blank']],
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
    'label' => Yii::$app->user->identity->nickname,
    'url' => ['#'],
    'active' => false,
    'items' => [
        [
            'label' => '<i class="fa fa-user"></i> ' . Yii::t('app', 'Profile'),
            'url' => ['/user'],
        ],
        [
            'label' => '<i class="fa fa-sign-out"></i> ' . Yii::t('app', 'Sign Out'),
            'url' => ['/role'],
        ],
    ],
];

echo Nav::widget([
    'options' => ['class' => 'navbar-nav navbar-right'],
    'items' => $menuItems,
    'encodeLabels' => false
]);