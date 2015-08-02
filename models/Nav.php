<?php

namespace app\models;

use Yii;
use app\components\BaseModel;

/**
 * This is the model class for table "{{%nav}}".
 *
 * @property integer $id
 * @property integer $pid
 * @property string $name
 * @property string $url
 * @property integer $order
 * @property string $extra
 * @property integer $create_time
 * @property integer $update_time
 *
 * @property Nav parent
 * @property Nav[] children
 */
class Nav extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%nav}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pid', 'order'], 'integer'],
            [['name'], 'required'],
            ['pid', 'default', 'value' => 0],
            [['name', 'url', 'extra'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => '父菜单',
            'name' => '名字',
            'url' => '网址',
            'order' => '显示顺序',
            'extra' => '附加属性',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert))
            return false;
        if ($insert)
            $this->create_time = time();

        $this->update_time = time();
        return true;
    }

    public function getParent()
    {
        return $this->hasOne(self::className(), ['id' => 'pid']);
    }

    public function getChildren()
    {
        return $this->hasMany(self::className(), ['pid' => 'id'])->orderBy(['order' => SORT_DESC]);
    }
}
