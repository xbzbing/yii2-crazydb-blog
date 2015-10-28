<?php
use yii\web\View;
use yii\helpers\Url;
use app\components\XUtils;
use app\models\User;
use app\modules\admin\widgets\Menu;

/**
 * @var View $this
 * @var User $current_user
 */
$current_url = Url::current();
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
                        'label' => '管理首页',
                        'url' => ['default/index'],
                        'icon' => 'fa fa-dashboard',
                        'active' => $current_url === Url::to(['default/index'])
                    ],
                    [
                        'label' => '文章管理',
                        'url' => ['post/index'],
                        'icon' => 'fa fa-list',
                    ],
                    [
                        'label' => '分类管理',
                        'url' => ['category/index'],
                        'icon' => 'fa fa-folder-open',
                    ],
                    [
                        'label' => '留言管理',
                        'url' => ['comment/index'],
                        'icon' => 'fa fa-comment',
                    ],
                    [
                        'label' => '用户管理',
                        'url' => ['user/index'],
                        'icon' => 'fa fa-user',
                    ],
                    [
                        'label' => '操作日志',
                        'url' => ['log/index'],
                        'icon' => 'fa fa-paw',
                    ],
                    [
                        'label' => '设置',
                        'type' => 'header'
                    ],
                    [
                        'label' => '系统配置',
                        'url' => ['config/index'],
                        'icon' => 'fa fa-cogs',
                        'options' => [
                            'class' => 'treeview',
                        ],
                        'items' => [
                            [
                                'label' => '基本设置',
                                'url' => ['config/setting'],
                                'icon' => 'fa fa-cog',
                            ],
                            [
                                'label' => '缓存管理',
                                'url' => ['config/cache'],
                                'icon' => 'fa fa-spinner',
                            ],
                            [
                                'label' => 'SEO 设置',
                                'url' => ['config/seo'],
                                'icon' => 'fa fa-google',
                            ],
                        ],
                    ],
                ]
            ]
        ); ?>
    </section>
</aside>