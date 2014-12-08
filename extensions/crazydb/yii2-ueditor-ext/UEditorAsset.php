<?php

namespace crazydb\ueditor;

use Yii;
use yii\web\AssetBundle;

class UEditorAsset extends AssetBundle
{
    public $sourcePath = '@crazydb/ueditor/assets';
    public $js = [
        'ueditor.config.js',
        'ueditor.all.min.js',
    ];

    public $css = [
        'themes/default/default.css','themes/simple/simple.css'
    ];

}
