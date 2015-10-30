<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\XUtils;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%log}}".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $type
 * @property string $action
 * @property string $result
 * @property string $key
 * @property string $detail
 * @property integer $create_time
 * @property string $ip
 * @property string $user_agent
 */
class Log extends ActiveRecord
{
    const TYPE_PERMISSION_DENY = 'permission deny';
    const TYPE_LOGIN = 'login';
    const TYPE_DEFAULT = 'default';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid'], 'required'],
            [['uid'], 'integer'],
            [['action', 'result', 'key'], 'string', 'max' => 100],
            [['detail', 'user_agent'], 'string', 'max' => 255],
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
            'action' => '执行操作',
            'type' => '操作类型',
            'result' => '操作结果',
            'key' => 'Key',
            'detail' => '详细信息',
            'create_time' => '操作时间',
            'ip' => '操作IP',
            'user_agent' => 'User Agent',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert))
            return false;

        if ($insert) {
            $this->create_time = time();
            $this->ip = XUtils::getClientIP();
            $this->user_agent = Yii::$app->request->userAgent;
        }

        $this->ip = htmlspecialchars($this->ip);
        $this->user_agent = htmlspecialchars($this->user_agent);
        $this->type = htmlspecialchars($this->type);
        $this->action = htmlspecialchars($this->action);
        $this->result = htmlspecialchars($this->result);
        $this->detail = htmlspecialchars($this->detail);
        return true;
    }

    /**
     * 记录一条日志
     * 会调用save()自动保存
     * @param array $logger
     * @return bool
     */
    public static function record($logger)
    {
        $log = new self();
        $log->uid = intval(ArrayHelper::getValue($logger, 'uid', 0));
        $log->type = ArrayHelper::getValue($logger, 'type', self::TYPE_DEFAULT);
        $log->action = ArrayHelper::getValue($logger, 'action');
        $log->key = ArrayHelper::getValue($logger, 'key');
        $log->detail = ArrayHelper::getValue($logger, 'detail');
        $log->result = ArrayHelper::getValue($logger, 'result', 'failed');
        return $log->save();
    }
}
