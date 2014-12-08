<?php

namespace crazydb\ueditor;

use Yii;
use yii\web\AssetBundle;

class UEditorAsset extends AssetBundle
{
    public $sourcePath = '@crazydb/ueditor/assets';

    /**
     * UEditor加载需要的JS文件。
     * ueditor.config.js中是默认配置项，不建议直接引入。
     * @var array
     */
    public $js = [
        'ueditor.all.min.js',
    ];

    /**
     * UEditor加载需要的CSS文件。
     * UEditor 会自动加载默认皮肤，CSS这里不必指定
     * @var array
     */
    public $css = [
    ];

}
