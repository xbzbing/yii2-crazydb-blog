<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-9-15 上午1:09
 */
namespace app\modules\admin\controllers;
use app\modules\admin\components\Controller;

class TestController extends Controller{

    public function actionIndex(){
        var_dump($this->view->theme);
    }
}