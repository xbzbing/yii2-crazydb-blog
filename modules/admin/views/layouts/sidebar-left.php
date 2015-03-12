<?php
use app\modules\admin\widgets\Menu;
use yii\helpers\Url;
/**
 * @var yii\web\View $this
 */

echo Menu::widget(
    [
        'options' => [
            'class' => 'sidebar-menu'
        ],
        'items' => [
            [
                'label' => Yii::t('app', 'Dashboard'),
                'url' => Url::to(['default/']),
                'icon' => 'fa-dashboard',
                'active' => Url::current() == Url::to(['default/index']),
            ],
            [
                'label' => Yii::t('app', 'Settings'),
                'url' => ['#'],
                'icon' => 'fa fa-spinner',
                'options' => [
                    'class' => 'treeview',
                ],
                'items' => [
                    [
                        'label' => Yii::t('app', 'Basic'),
                        'url' => ['/basic/index'],
                        'icon' => 'fa fa-user',
                    ],
                    [
                        'label' => Yii::t('app', 'Advanced'),
                        'url' => ['/advanced/index'],
                        'icon' => 'fa fa-lock',
                    ],
                ],
            ],
            [
                'label' => Yii::t('app', 'System'),
                'url' => ['#'],
                'icon' => 'fa fa-cog',
                'options' => [
                    'class' => 'treeview',
                ],
                'items' => [
                    [
                        'label' => Yii::t('app', 'User'),
                        'url' => ['/user/index'],
                        'icon' => 'fa fa-user',
                    ],
                    [
                        'label' => Yii::t('app', 'Role'),
                        'url' => ['/role/index'],
                        'icon' => 'fa fa-lock',
                    ],
                ],
            ],
        ]
    ]
);