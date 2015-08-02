<?php

namespace app\models;


use Yii;
use \yii\db\ActiveRecord;
use \app\components\XUtils;

/**
 * This is the model class for table "{{%logger}}".
 *
 * @property string $id 日志ID
 * @property string $uid 用户ID
 * @property string $status 操作状态(是否成功)
 * @property string $optype 操作类型
 * @property string $info 详细信息
 * @property integer $create_time 操作时间
 * @property string $ip 用户IP
 * @property string $user_agent 用户UA
 */
class Logger extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%logger}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'create_time', 'ip'], 'required'],
            [['uid', 'create_time'], 'integer'],
            [['status', 'optype'], 'string', 'max' => 40],
            [['info', 'user_agent'], 'string', 'max' => 255],
            [['ip'], 'string', 'max' => 15]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '日志ID',
            'uid' => '用户ID',
            'status' => '操作状态',
            'optype' => '操作类型',
            'info' => '详细信息',
            'create_time' => '操作时间',
            'ip' => '操作IP',
            'user_agent' => 'User_Agent',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert))
            return false;
        if ($insert) {
            $this->create_time = time();
            $this->ip = XUtils::getClientIP();
            $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        }
        $this->ip = htmlspecialchars($this->ip);
        $this->user_agent = htmlspecialchars($this->user_agent);
        $this->status = htmlspecialchars($this->status);
        $this->optype = htmlspecialchars($this->optype);
        $this->info = htmlspecialchars($this->info);
    }

    /**
     * 记录一条日志
     * 会调用save()自动保存
     * @param integer $uid 操作者ID
     * @param string $optype 操作类型
     * @param string $info 具体信息
     * @param string $status 操作是否成功
     */
    public static function record($uid, $optype, $info, $status = 'None')
    {
        $logger = new self();
        $logger->uid = intval($uid);
        $logger->optype = $optype;
        $logger->info = $info;
        $logger->status = $status;
        $logger->save();
    }
}
