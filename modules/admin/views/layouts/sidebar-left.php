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
                'encodeLabels' => false,
                'items' => [
                    [
                        'label' => '<span>管理首页</span>',
                        'url' => ['default/index'],
                        'icon' => 'fa fa-dashboard',
                        'active' => $current_url === Url::to(['default/index'])
                    ],
                    [
                        'label' => '<span>文章管理</span>',
                        'url' => ['post/index'],
                        'icon' => 'fa fa-list',
                    ],
                    [
                        'label' => '<span>分类管理</span>',
                        'url' => ['category/index'],
                        'icon' => 'fa fa-folder-open',
                    ],
                    [
                        'label' => '<span>留言管理</span>',
                        'url' => ['comment/index'],
                        'icon' => 'fa fa-comment',
                    ],
                    [
                        'label' => '<span>用户管理</span>',
                        'url' => ['user/index'],
                        'icon' => 'fa fa-user',
                    ],
                    [
                        'label' => '<span>导航</span>',
                        'url' => ['nav/index'],
                        'icon' => 'fa fa-map-marker',
                    ],
                    [
                        'label' => '<span>操作日志</span>',
                        'url' => ['log/index'],
                        'icon' => 'fa fa-paw',
                    ],
                    [
                        'label' => '<span>设置</span>',
                        'options' => ['class' => 'header']
                    ],
                    [
                        'label' => '<span>系统配置</span>',
                        'url' => ['config/index'],
                        'icon' => 'fa fa-cogs',
                        'options' => [
                            'class' => 'treeview',
                        ],
                        'items' => [
                            [
                                'label' => '<span>基本设置</span>',
                                'url' => ['config/setting'],
                                'icon' => 'fa fa-cog',
                            ],
                            [
                                'label' => '<span>缓存管理</span>',
                                'url' => ['config/cache'],
                                'icon' => 'fa fa-spinner',
                            ],
                            [
                                'label' => '<span>SEO 设置</span>',
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