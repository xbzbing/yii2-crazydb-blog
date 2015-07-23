<?php
/**
 * @author xbzbing<xbzbing@gmail.com>
 * @date 14-6-6 上午11:02
 */

namespace app\components;

use yii;
use yii\web\Controller;

class BaseController extends Controller
{

    /**
     * 前端controller初始化
     */
    public function init()
    {
        $this->checkTheme();
    }


    /**
     * 前端初始化之一：检测主题设置
     *
     * 独立出来的原因是不想在init中直接抛出异常。。。
     *
     * @throws yii\base\InvalidConfigException
     */
    public function checkTheme()
    {
        $config = CMSUtils::getSiteConfig('sys');
        $themes = CMSUtils::getThemeList();

        if ($config['theme'] != '[none]' && !in_array($config['theme'], $themes))
            throw new yii\base\InvalidConfigException(Yii::t('app', 'Can\'t find any theme called "{theme}",please check the "Theme Config".', ['theme' => $config['theme']]));

        if ($config['theme'] != '[none]') {
            $this->view->theme = Yii::createObject([
                'class' => '\yii\base\Theme',
                'pathMap' => ['@app/views' => '@app/themes/' . $config['theme']],
            ]);
        }
    }
}