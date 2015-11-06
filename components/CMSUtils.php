<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-12 下午5:05
 */
namespace app\components;

use yii;
use yii\caching\DbDependency;
use yii\db\Query;
use app\models\Option;

class CMSUtils
{

    /**
     * 获取Option信息
     * @param string $type 类型 默认为sys
     * @param bool $refresh 强制刷新
     * @return array $config 配置数组
     */
    public static function getSiteConfig($type = 'sys', $refresh = false)
    {

        $cache_key = "config_{$type}";

        if ($refresh)
            $config = null;
        else
            $config = Yii::$app->cache->get($cache_key);


        if (empty($config)) {
            $options = Option::find()->where(['type' => $type])->asArray()->all();
            foreach ($options as $op) {
                $config[$op['name']] = $op['value'];
            }
            $dp = new DbDependency();
            $dp->sql = (new Query())
                ->select('MAX(update_time)')
                ->from(Option::tableName())
                ->createCommand()->rawSql;

            Yii::$app->cache->set(
                $cache_key,
                $config,
                3600,
                $dp
            );
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
        if (!is_readable($basePath))
            return $themes;
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