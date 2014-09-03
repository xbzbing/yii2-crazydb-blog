<?php

namespace app\components;
use Yii;
use yii\web\Cookie;
class Common
{
	public static function setLanguage($language='')
	{
		$options = ['name'=>'language','value'=>$language,'expire'=>time()+86400*365];
		$cookie = new Cookie($options);
		Yii::$app->response->cookies->add($cookie);
	}

	public static function getLanguage()
	{
		if (Yii::$app->request->cookies['language']) {
		    return Yii::$app->request->cookies->getValue('language');
		}else{
			return false;
		}
	}

}
