<?php

namespace app\modules\admin\components;

use Yii;

class Controller extends \yii\web\Controller
{
    public $layout = 'column1';

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

}
