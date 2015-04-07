<?php
namespace app\assets;

use yii\web\AssetBundle;

/**
 * 临时前端管理工具
 * 因为后期采用主题形式开发
 * Class AppAsset
 * @package app\assets
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web/static';
    public $css = [
        'css/ext.css'
    ];
    public $js = [
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
