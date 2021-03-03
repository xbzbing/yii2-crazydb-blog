<?php
namespace app\modules\admin\assets;

use Yii;
use yii\web\AssetBundle;

/**
 * Class AdminlteAsset
 * admin-lte的asset
 * @package app\modules\admin\assets
 */
class AdminlteAsset extends AssetBundle
{
    public $sourcePath = '@vendor/almasaeed2010/adminlte';
    public $css = [
        'dist/css/skins/_all-skins.min.css',
    ];
    public $js = [
        /*
            bootstrap-slider
            bootstrap-wysihtml5
            iCheck
            input-mask
            jQueryUI
            jvectormap
            pace
            seiyria-bootstrap-slider
            timepicker
        */
        'dist/js/adminlte.min.js',
    ];

    public $depends = [
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset'
    ];
    public $publishOptions = [
        'except' => [
            '/*.html',
            '/*.json',
            '/*.md',
            '/*.js',
            'pages/'
        ]
    ];

}
