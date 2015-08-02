<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-12 下午5:05
 */
namespace app\components;

use yii;
use yii\caching\DbDependency;
use app\models\Category;
use app\models\Nav;
use app\models\Option;

class CMSUtils
{

    /**
     * 获取父类导航。
     * @param bool $refresh 强制刷新
     * @return array
     */
    public static function getParentNav($refresh = false)
    {
        if ($refresh)
            $items = [];
        else
            $items = Yii::$app->cache->get('__parent_nav');

        if (empty($items)) {
            $item_array = Nav::find()->select('id,name')->where(['pid' => 0])->orderBy(['order' => SORT_DESC])->asArray()->all();
            if(empty($item_array))
                return [];
            foreach ($item_array as $item) {
                $items[$item['id']] = $item['name'];
            }
            $dp = new DbDependency();
            $dp->sql = 'select MAX(update_time) from ' . Nav::tableName();
            Yii::$app->cache->set(
                '__categories',
                $items,
                0,
                $dp
            );
        }
        return $items;
    }

    /**
     * 获取导航树。
     * @param bool $refresh 强制刷新
     * @return array
     */
    public static function getNavTree($refresh = false)
    {
        if ($refresh)
            $items = [];
        else
            $items = Yii::$app->cache->get('__nav_tree');

        if (empty($items)) {
            /* @var Nav[] $parent */
            $parent = Nav::find()->where(['pid' => 0])->orderBy(['order' => SORT_DESC])->all();;
            if(empty($parent))
                return $items;

            foreach($parent as $node){
                $items[$node->id] = [
                    'label' => $node->name,
                    'url' => $node->url,
                    'items' => []
                ];
                $children = $node->children;
                foreach($children as $child){
                    $items[$node->id]['items'] = [
                        'label' => $child->name,
                        'url' => $node->url,
                    ];
                }
            }
            $dp = new DbDependency();
            $dp->sql = 'select MAX(update_time) from ' . Nav::tableName();
            Yii::$app->cache->set(
                '__nav_tree',
                $items,
                0,
                $dp
            );
        }
        return $items;
    }

    /**
     * 获取文章分类。
     * @param bool $refresh 强制刷新
     * @return array
     */
    public static function getAllCategories($refresh = false)
    {
        if ($refresh)
            $categories = [];
        else
            $categories = Yii::$app->cache->get('__categories');

        if (empty($categories)) {
            $category_array = Category::find()->select('id,name')->asArray()->all();
            if(empty($category_array))
                return [];
            foreach ($category_array as $category) {
                $categories[$category['id']] = $category['name'];
            }
            $dp = new DbDependency();
            $dp->sql = 'select MAX(update_time) from ' . Category::tableName();
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
    public static function getSiteConfig($type = 'sys', $refresh = false)
    {
        //得益于php奇葩的变量作用域。- -不知道是否应该向上兼容的规范化。。。。
        if ($refresh)
            $config = null;
        else
            $config = Yii::$app->cache->get("config_{$type}");


        if (empty($config)) {
            $options = Option::find()->where(['type' => $type])->asArray()->all();
            foreach ($options as $op) {
                $config[$op['name']] = $op['value'];
            }
            Yii::$app->cache->set("config_{$type}", $config, 3600);

        }
        return $config;
    }

    /**
     * 获取themes文件夹下的文件
     * @return array
     */
    public static function getThemeList()
    {
        $themes = [];
        $basePath = Yii::getAlias('@app/themes');
        if(!is_readable($basePath))
            return [];
        $folder = @opendir($basePath);
        //TODO 主题部分支持开xml或者其他格式的自定义
        while (($file = @readdir($folder)) !== false) {
            if (substr($file, 0, 1) != '.' && is_dir($basePath . DIRECTORY_SEPARATOR . $file) && htmlspecialchars($file) == $file)
                $themes[$file] = $file;
        }
        closedir($folder);

        ksort($themes);
        return $themes;
    }
}