<?php

namespace app\modules\admin\components;

use Yii;
use app\components\Common;

class Controller extends \yii\web\Controller
{
	public $layout = 'column2';

	public function beforeAction($action) 
	{
		if (parent::beforeAction($action)) {

            if (!Common::getLanguage()) {
                preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                Common::setLanguage($matches[1]);
                Yii::$app->language=$matches[1]; 
            }else{
               Yii::$app->language=Common::getLanguage(); 
            }
            return true;
        } else {
            return false;
        }
    }
}
