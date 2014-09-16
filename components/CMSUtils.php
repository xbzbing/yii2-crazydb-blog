<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-12 下午5:05
 */
namespace app\components;
use yii;
use yii\caching\DbDependency;
use app\models\Category;
use app\models\Option;

class CMSUtils{

    /**
     * 获取文章分类。
     * 缓存时间1小时。
     * @param bool $refresh 强制刷新
     * @return mixed
     */
    public static function getAllCategories($refresh = false){
        if($refresh)
            $categories = null;
        else
            $categories = Yii::$app->cache->get('__categories');

        if(empty($categories)){
            $category_array = Category::find()->select('id,name')->asArray()->all();
            foreach($category_array as $category){
                $categories[$category['id']] = $category['name'];
            }
            $dp = new DbDependency();
            $dp->sql = 'select MAX(id) from '.Category::tableName();
            Yii::$app->cache->set(
                '__categories',
                $categories,
                0,
                $dp
            );
        }
        return $categories;
    }

    /**
     * 获取Option信息
     * @param string $type 类型 默认为sys
     * @param bool $refresh 强制刷新
     * @return array $config 配置数组
     */
    public static function getSiteConfig($type = 'sys',$refresh = false){
        //得益于php奇葩的变量作用域。- -不知道是否应该向上兼容的规范化。。。。
        if($refresh)
            $config = null;
        else
            $config = Yii::$app->cache->get("config_{$type}");


        if(empty($config)){
            $options = Option::findAll("type=:type",array('type'=>$type));

            foreach($options as $op){
                $config[$op->name] = $op->value;
            }
            Yii::app()->cache->set("config_{$type}",$config,3600);

        }
        return $config;
    }
}