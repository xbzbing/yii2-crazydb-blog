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
    public $sourcePath = '@bower/adminlte';
    public $css = [
        'dist/css/skins/skin-blue.min.css',
        'dist/css/AdminLTE.min.css'
    ];
    public $js = [
        /*
        '//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js',
        'plugins/morris/morris.min.js',
        'plugins/sparkline/jquery.sparkline.min.js',
        'plugins/jvectormap/jquery-jvectormap-1.2.2.min.js',
        'plugins/jvectormap/jquery-jvectormap-world-mill-en.js',
        'plugins/knob/jquery.knob.js',
        'plugins/daterangepicker/daterangepicker.js',
        'plugins/datepicker/bootstrap-datepicker.js',
        'plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js',
        'plugins/iCheck/icheck.min.js',
        'plugins/slimScroll/jquery.slimscroll.min.js',
        'plugins/fastclick/fastclick.min.js',
        */
        'dist/js/app.min.js'
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
