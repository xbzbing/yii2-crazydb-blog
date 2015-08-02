<?php

namespace app\models;

use app\components\BaseModel;
use Yii;
use yii\helpers\Html;

/**
 * This is the model class for table "{{%option}}".
 *
 * @property string $type
 * @property string $name
 * @property string $value
 * @property string $description
 */
class Option extends BaseModel
{
    /**
     * sys类型，系统配置
     */
    const TYPE_SYS = 'sys';
    /**
     * seo类型，seo设置
     */
    const TYPE_SEO = 'seo';


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%option}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'type'], 'required'],
            ['value', 'string'],
            ['type', 'string', 'max' => 20],
            ['name', 'string', 'max' => 50],
            ['name', 'regString', 'type' => self::REG_BLANK],
            ['type', 'regString', 'type' => self::REG_LETTER],
            ['description', 'string', 'max' => 255],
        ];
    }

    function beforeSave($insert)
    {
        /**
         * 这里制作encode，特殊点需要decode
         */
        $this->description = Html::encode($this->description);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'type' => '配置类型',
            'name' => '配置名称',
            'value' => '值',
            'description' => '描述',
        ];
    }
}
