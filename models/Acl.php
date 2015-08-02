<?php

namespace app\models;

use Yii;
use \yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%acl}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $action
 * @property string $info
 */
class Acl extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%acl}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'name', 'action'], 'required'],
            [['id'], 'integer'],
            [['action', 'info'], 'string'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '角色名称',
            'action' => '允许的操作',
            'info' => '角色描述',
        ];
    }
}
