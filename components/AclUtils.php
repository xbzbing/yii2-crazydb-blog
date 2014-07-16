<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-13 上午11:05
 */
namespace app\components;
use yii;
use app\models\Acl;

class AclUtils{

    const ADMIN = 'Administrator';
    const AUTHOR = 'Author';
    const EDITOR = 'Editor';
    const MANAGER = 'Manager';
    const USER = 'User';
    const GUEST = 'Guest';

    /**
     * 获得acl分组，将action转换为数组，默认缓存1小时。
     * acl存储为三维数组，而不是acl实例的数组。
     * 格式为：
     * array(
     *  'acl id'=>array(
     *              'name'=>'acl name',
     *              'action=>array('action1','action2')
     *          )
     * )
     * @param bool $refresh
     * @return array
     */
    public static function getAllAcl($refresh=false){

        if($refresh)
            $acl = null;
        else
            $acl = Yii::$app->cache->get('__allAcl');

        if(empty($acl)){
            $roles = Acl::findAll([]);
            foreach($roles as $role){
                $role->action = explode(',',$role->action);
                $role->action = array_filter($role->action);
                $acl[$role->id] = ['id'=>$role->id,'name'=>$role->name,'action'=>$role->action];
            }
            Yii::$app->cache->set('__allAcl',$acl,3600);
        }
        return $acl;
    }

}