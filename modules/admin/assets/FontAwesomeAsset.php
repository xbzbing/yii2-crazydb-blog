<?php
namespace app\modules\admin\assets;

use Yii;
use yii\web\AssetBundle;

/**
 * Class FontAwesomeAsset
 * font-awesome的asset
 * @package app\modules\admin\assets
 */
class FontAwesomeAsset extends AssetBundle
{
    public $sourcePath = '@vendor/fortawesome/font-awesome';
    public $css = [];
    public $js = [];
    public $depends = [
        'yii\web\YiiAsset',
    ];

    public function init(){
        parent::init();
        if(YII_ENV_DEV){
            $this->css += ['css/font-awesome.css'];
        }else{
            $this->css += ['css/font-awesome.min.css'];
        }
    }
}
