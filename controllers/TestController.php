<?php
/**
 * Created by PhpStorm.
 * User: xbzbing
 * Date: 14-7-19
 * Time: 上午12:15
 */

namespace app\controllers;
use Yii;
use app\models\User;
use yii\web\Controller;

class TestController extends Controller{
    public function actionIndex(){
        $user = User::findByUsername('xbzbing');
        $user->setScenario('modifyPassword');
        $user->password = 'admin';
        $user->save();
        return $this->render('test',['user'=>$user]);
    }
    public function actionAdd(){
        $user = new User();
        $user->setScenario('modifyPassword');
        $user->password = '123';
        $user->name = 'xxxxx';
        $user->nickname = 'xxxxx';
        $user->email = 'asdfasd';
        $user->save();
        return $this->render('test',['user'=>$user]);
    }
} 