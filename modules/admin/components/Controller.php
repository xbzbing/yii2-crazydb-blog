<?php

namespace app\modules\admin\components;

use Yii;
use app\components\Common;

class Controller extends \yii\web\Controller
{
	public $layout = 'column1';
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }
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
