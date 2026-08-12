<?php
namespace app\modules\admin\assets;

use yii\web\AssetBundle;


/**
 * Class AdminAsset
 * 后台自定义的asset
 * @package app\modules\admin\assets
 */
class AdminAsset extends AssetBundle
{
    public $sourcePath = '@CrazydbAdmin/assets/resources';
    public $css = [
        'css/common.css',
        'css/AdminLTE.min.css',
        'plugins/messenger/css/messenger.css',
    ];
    public $js = [
        'js/main.js',
        'plugins/messenger/js/messenger.min.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
