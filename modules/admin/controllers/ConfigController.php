<?php
namespace app\modules\admin\controllers;

use Yii;

use app\components\CMSUtils;
use app\components\XUtils;
use app\modules\admin\components\Controller;
use app\modules\admin\models\SettingForm;
use app\models\Logger;

/**
 * Default controller
 */
class ConfigController extends Controller
{

    /**
     * Base Setting
     * 基本设置
     */
    public function actionSetting(){
        $setting = new SettingForm();
        $setting->attributes = CMSUtils::getSiteConfig( 'sys' );
        $themes = array('[none]'=>'不使用主题');
        $themes += XUtils::getThemeList();
        if( isset( $_POST['OptionForm'] ) ){
            $setting->attributes = $_POST['SettingForm'];
            if( $row = $setting->save( 'sys' ) ){
                Yii::$app->cache->set( 'config_sys', $setting->attributes );
                Logger::record( Yii::app()->user->id, 'config', "修改网站配置({$row}项)" );
            }
        }
        return $this->render('setting',['model'=>$setting]);
	}

    /**
     * 系统SEO设置
     */
    public function actionSEO(){
	}

    /**
     * 系统Slider设置
     */
    public function actionSlider(){

    }

    /**
     * 系统缓存设置
     */
    public function actionCache(){

    }

}
