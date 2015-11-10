<?php
namespace app\components;

use yii;
use yii\web\Controller;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use app\models\Option;
use app\models\User;

class BaseController extends Controller
{
    public $layout = 'column1';

    public $enableTheme = true;
    /**
     * 前端controller初始化
     */
    public function init()
    {
        parent::init();

        $seoConfig = CMSUtils::getSiteConfig('seo');
        $this->view->params[Option::SEO_KEYWORDS] = ArrayHelper::getValue($seoConfig, Option::SEO_KEYWORDS);
        $this->view->params[Option::SEO_DESCRIPTION] = ArrayHelper::getValue($seoConfig, Option::SEO_DESCRIPTION);

        $config = CMSUtils::getSiteConfig('sys');

        if ($this->enableTheme && !empty($config['theme']))
            $this->setTheme($config['theme']);

        Yii::$app->params = ArrayHelper::merge(Yii::$app->params, $config);

        if(!Yii::$app->user->isGuest){
            $cache_key = '__user_active_time_' . Yii::$app->user->id;
            $active_time = Yii::$app->cache->get($cache_key);
            if(!$active_time){
                User::updateAll(['active_time' => time()], ['id' => Yii::$app->user->id]);
                Yii::$app->cache->set($cache_key, time(), 600);//10分钟
            }
        }
    }

    /**
     * 前端初始化之一：检测主题设置
     *
     * 独立出来的原因是不想在init中直接抛出异常。。。
     *
     * @param string $theme
     * @return bool
     * @throws InvalidConfigException
     */
    public function setTheme($theme)
    {
        $themes = CMSUtils::getThemeList();
        if ($theme != '[none]' && !in_array($theme, $themes))
            throw new InvalidConfigException("错误的配置项，不支持的主题类型:「$theme」。");
        if ($theme != '[none]') {
            $this->view->theme = Yii::createObject([
                'class' => '\yii\base\Theme',
                'pathMap' => ['@app/views' => "@app/themes/{$theme}/views"],
            ]);
        }
        return true;
    }
}