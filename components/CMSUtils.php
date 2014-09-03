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
}