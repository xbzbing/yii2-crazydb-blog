<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-12 下午5:05
 */
namespace app\components;

use yii;
use yii\caching\DbDependency;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use app\models\Option;

class CMSUtils
{

    public static function getSmilies()
    {
        return [
            'question',
            'razz',
            'sad',
            'evil',
            'exclaim',
            'smile',
            'redface',
            'biggrin',
            'surprised',
            'eek',
            'confused',
            'cool',
            'lol',
            'mad',
            'twisted',
            'rolleyes',
            'wink',
            'idea',
            'arrow',
            'neutral',
            'cry',
            'mrgreen'
        ];
    }

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
     * 获取指定的key
     * @param $key
     * @param bool|false $fresh
     * @return mixed
     */
    public static function getSysConfig($key, $fresh = false)
    {
        static $config = [];
        if (empty($config) || $fresh)
            $config = self::getSiteConfig('sys', $fresh);
        return ArrayHelper::getValue($config, $key);
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

    /**
     * 系统通知机制
     * @param string $to
     * @param string $title
     * @param string $content
     * @param string $nickname
     * @return bool
     */
    public static function notice($to, $nickname, $title, $content)
    {
        $admin_email = ArrayHelper::getValue(Yii::$app->params, 'admin_email');
        $notice_email = ArrayHelper::getValue(Yii::$app->params, 'notice_email');
        if(isset(Yii::$app->mailer) && $admin_email && $notice_email)
            Yii::$app->mailer
                ->compose('@app/mail/notice', ['name' => Html::encode($nickname), 'title' => $title, 'message' => $content])
                ->setFrom($notice_email)
                ->setTo(YII_DEBUG ? $admin_email : $to)
                ->setSubject($title)
                ->send();

        return true;
    }
}