<?php
namespace app\components;

use app\models\Option;
use yii;
use yii\web\Controller;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

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

        $config = CMSUtils::getSiteConfig('sys');

        if ($this->enableTheme && !empty($config['theme']))
            $this->setTheme($config['theme']);

        $seoConfig = CMSUtils::getSiteConfig('seo');
        Yii::$app->params = ArrayHelper::merge(Yii::$app->params, $seoConfig, $config);
        $this->view->params[Option::SEO_KEYWORDS] = ArrayHelper::getValue($seoConfig, Option::SEO_KEYWORDS);
        $this->view->params[Option::SEO_DESCRIPTION] = ArrayHelper::getValue($seoConfig, Option::SEO_DESCRIPTION);
        Yii::$app->response->headers->set('X-Frame-Options', 'SAMEORIGIN');
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