<?php
use yii\bootstrap\Nav;
use yii\helpers\Html;
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
            'url' => ['/user/profile'],
        ],
        '<li><a href="#" title="退出" id="logout-btn" type="submit"><i class="fa fa-sign-out"> </i> 退出</a>'
        . Html::beginForm(['/site/logout'], 'post', ['id' => 'logout-form'])
        . Html::endForm()
        . '</li>'
    ],
];

$menuItems[] = ['label' => "<i class=\"fa fa-cog\"> </i>", 'url' => '#', 'linkOptions' => ['data-toggle' => 'control-sidebar']];

echo Nav::widget([
    'options' => ['class' => 'nva navbar-nav'],
    'items' => $menuItems,
    'encodeLabels' => false
]);