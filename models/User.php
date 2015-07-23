<?php

namespace app\models;

use Yii;

use \yii\base\NotSupportedException;
use \yii\db\ActiveRecord;
use \yii\web\IdentityInterface;

use app\components\XUtils;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property string $id
 * @property string $nickname
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $url
 * @property string $acl
 * @property string $reg_ip
 * @property integer $reg_time
 * @property integer $update_time
 * @property string $salt
 * @property string $auth_key
 * @property integer $status
 * @property string $info
 * @property string $ext
 *
 * #getter
 * @property string $userStatus
 */
class User extends ActiveRecord implements IdentityInterface
{

    const STATUS_NORMAL = 1;
    const STATUS_INACTIVE = 2;
    const STATUS_BANED = 4;
    const STATUS_DELETED = 8;

    /**
     * 黑名单
     * @var string[]
     */
    public $nameBlackList = ['admin'];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'password', 'email'], 'required'],
            [['reg_time', 'update_time', 'status'], 'integer'],
            [['status'], 'default', 'value' => self::STATUS_NORMAL],
            [['status'], 'in', 'range' => array_keys(self::getAvailableStatus())],
            [['info', 'ext'], 'string'],
            [['nickname'], 'string', 'max' => 80],
            [['username'], 'string', 'max' => 20],
            [['password'], 'string', 'max' => 60],
            [['password'], 'string', 'max' => 20, 'on' => ['register', 'modifyPassword']],
            [['email', 'url', 'acl'], 'string', 'max' => 100],
            [['email'], 'email'],
            [['reg_ip'], 'string', 'max' => 15],
            [['salt'], 'string', 'max' => 60],
            [['username', 'nickname', 'email'], 'unique']
        ];
    }

    /**
     * 获得可能的状态值
     * @return string[]
     */
    public static function getAvailableStatus()
    {
        return [
            self::STATUS_NORMAL => '正常',
            self::STATUS_INACTIVE => '未激活',
            self::STATUS_BANED => '账号被禁用',
            self::STATUS_DELETED => '已删除'
        ];
    }

    /**
     * 获得用户状态对应的名称
     * @param $status
     * @return null|string
     */
    public static function getStatusName($status)
    {
        $statuses = self::getAvailableStatus();
        return isset($statuses[$status]) ? $statuses[$status] : null;
    }

    public function getUserStatus()
    {
        $status = self::getAvailableStatus();
        if (isset($status[$this->status]))
            return $status[$this->status];
        else
            return '未设置';
    }

    /**asfdsdfas
     * @inheritdoc
     */
    public function attributeLabels()
    {

        return [
            'id' => '用户ID',
            'nickname' => '昵称',
            'username' => '用户名',
            'password' => '密码',
            'email' => '电子邮箱',
            'url' => 'Blog URL',
            'acl' => '访问权限控制',
            'reg_ip' => '注册IP',
            'reg_time' => '注册时间',
            'update_time' => '更新时间',
            'salt' => '带盐',
            'auth_key' => '授权代码',
            'status' => '用户状态',
            'info' => '个人简介',
            'ext' => '保留字段',
            'userStatus' => '用户状态'
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert))
            return false;
        if ($this->isNewRecord) {
            $this->reg_time = time();
            $this->reg_ip = XUtils::getClientIP();
            $this->auth_key = Yii::$app->security->generateRandomKey();
        } else {
            $this->update_time = time();
        }
        //注册黑名单
        if (in_array($this->username, $this->nameBlackList) || in_array($this->nickname, $this->nameBlackList)) {
            $this->$this->addError('username', '该用户名不能被注册！');
        }
        if (in_array($this->scenario, array('register', 'modifyPassword'))) {
//            $this->salt = 20;
            $this->password = $this->hashPassword($this->password);
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param  string $username
     * @param int $status
     * @return static|null
     */
    public static function findByUsername($username, $status = self::STATUS_NORMAL)
    {
        return static::findOne(['username' => $username, $status]);
    }

    /**
     * Finds user by password reset token
     *
     * @param  string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int)end($parts);
        if ($timestamp + $expire < time()) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_NORMAL,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    /**
     * 加密密码
     * @param string $password
     * @return string
     */
    public function hashPassword($password)
    {
        return Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomKey();
    }

}
