<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-12 下午5:05
 */
namespace app\components;
use yii;
use yii\caching\DbDependency;
use app\models\Category;

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
            $post_cate = Category::findAll([]);
            $dp = new DbDependency();
            $dp->sql = 'select MAX(id) from '.Category::tableName();
            foreach($post_cate as $cate)
                $categories[$cate->id] = $cate->name;
            Yii::$app->cache->set(
                '__categories',
                $categories,
                0,
                $dp
            );
        }
        return $categories;
    }
}