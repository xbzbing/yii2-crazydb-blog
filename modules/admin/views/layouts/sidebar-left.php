<?php
use yii\web\View;
use app\components\XUtils;
use app\models\User;
use app\modules\admin\widgets\Menu;

/**
 * @var View $this
 * @var User $current_user
 */
$current_user = Yii::$app->user->identity;

?>
<aside class="main-sidebar">
    <section class="sidebar">
        <div class="user-panel">
            <div class="pull-left image">
                <img src="<?= XUtils::getAvatar($current_user->email) ?>" class="img-circle" alt="<?= $current_user->nickname ?>">
            </div>
            <div class="pull-left info">
                <p><?= $current_user->nickname ?></p>
                <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
        </div>
        <?= Menu::widget(
            [
                'options' => [
                    'class' => 'sidebar-menu'
                ],
                'items' => [
                    [
                        'label' => Yii::t('app', 'Dashboard'),
                        'url' => Yii::$app->homeUrl,
                        'icon' => 'fa-dashboard',
                        'active' => Yii::$app->request->url === Yii::$app->homeUrl
                    ],
                    [
                        'label' => Yii::t('app', 'Settings'),
                        'url' => ['#'],
                        'icon' => 'fa fa-spinner',
                        'options' => [
                            'class' => 'treeview',
                        ],
                        'visible' => Yii::$app->user->can('readPost'),
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
                                //'visible' => (Yii::$app->user->identity->username == 'admin'),
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
        ); ?>
    </section>
</aside>