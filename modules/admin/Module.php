<?php

namespace app\modules\admin;

use Yii;
use yii\web\ForbiddenHttpException;
use app\components\AclUtils;
use app\models\Logger;

class Module extends \yii\base\Module
{
    public $name = '管理后台';
    public $controllerNamespace = 'app\modules\admin\controllers';
    public $allowedIPs;
    /**
     * 允许访问的角色
     * @var array
     */
    public $roles = ['Author','Editor','Manager','Administrator'];
    /**
     * 允许直接访问的路由
     * @var array
     */
    public $excludedRoute=[];

    public function init(){
        parent::init();
        if(!is_array($this->excludedRoute)){
            Yii::trace('AdminModule.excludedRoute配置错误','Config');
            $this->excludedRoute = [];
        }
    }
    /**
     * @inheritdoc
     */
    public function beforeAction($action){
        $route=Yii::$app->controller->id.'/'.$action->id;
        
        if(!$this->checkAccess($route)){
            throw new ForbiddenHttpException(Yii::t('app', 'You are not allowed to access this page.'));
        }

        if(!$this->checkRole()){
            throw new ForbiddenHttpException('当前角色没有访问该模块的权限。');
        }

        return parent::beforeAction($action);
    }
    /**
     * @todo IP校验
     * @param $route
     * @return bool
     */
    protected function checkAccess($route){
        if(in_array($route,$this->excludedRoute)){
            return true;
        }
        if(Yii::$app->user->isGuest)
            return false;
        else
            return false;
    }

    /**
     * 访问角色
     * @return bool
     */
    protected function checkRole(){

        if(!is_array($this->roles))
            return false;

        if(in_array('*',$this->roles))
            return true;

        $userAcl = Yii::$app->user->isGuest ? ['Guest'] : Yii::$app->user->identityClass->alc;
        $userAcl = explode(',',$userAcl);
        $acl = AclUtils::getAllAcl();
        $currentRole = '';
        foreach($userAcl as $id){
            if(!isset($acl[$id]))
                break;
            $currentRole .= $acl[$id]['name'];
            if(in_array($acl[$id]['name'],$this->roles) || $acl[$id]['name'] == AclUtils::ADMIN){
                return true;
            }
        }
        if(!$currentRole)
            $currentRole = 'Guest';
        Logger::record(
            Yii::$app->user->id,
            '越权访问',
            '用户[ '.Yii::$app->user->name." ]以[ $currentRole ]权限访问「{$this->name}」模块被拒绝。",
            '失败'
        );
        return false;
    }
}
