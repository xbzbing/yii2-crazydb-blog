<?php

namespace app\models;

use Yii;

use \yii\base\NotSupportedException;
use \yii\db\ActiveRecord;
use \yii\helpers\Security;
use \yii\web\IdentityInterface;

use app\components\XUtils;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property string $id
 * @property string $nickname
 * @property string $name
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
 */
class User extends ActiveRecord implements IdentityInterface {
    const STATUS_NORMAL = 1;
    const STATUS_INACTIVE = 2;
    const STATUS_BANED = 4;
    const STATUS_DELETED = 8;

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [['nickname', 'name', 'password', 'email'], 'required'],
            [['ret_time, update_time', 'status'], 'integer'],
            [['info', 'ext'], 'string'],
            [['nickname', 'name'], 'string', 'max' => 80],
            [['password'], 'string', 'max' => 32],
            [['email', 'url', 'acl'], 'string', 'max' => 100],
            [['ret_ip'], 'string', 'max' => 15],
            [['salt'], 'string', 'max' => 60]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(){

        return [
            'id' => '用户ID',
            'nickname' => '昵称',
            'name' => '用户名',
            'password' => '密码',
            'email' => '电子邮箱',
            'url' => 'Blog URL',
            'acl' => '访问权限控制',
            'ret_ip' => '注册IP',
            'ret_time' => '注册时间',
            'salt' => '带盐',
            'auth_key'=>'授权代码',
            'status' => '用户状态',
            'info' => '个人简介',
            'ext' => '保留字段',
        ];
    }

    public function beforeSave($insert){
        if(!parent::beforeSave($insert))
            return false;
        if($insert){
            $this->reg_time = time();
            $this->reg_ip = XUtils::getClientIP();
            //注册黑名单
            if($this->name=='admin' || $this->nickname == 'admin'){
                $this->addError('name','用户名已被注册！');
            }
        }else{
            $this->update_time = time();
        }

        if(in_array($this->scenario, array('register','modifyPassword'))){
            $this->salt = $this->generateSalt();
            $this->password = $this->hashPassword($this->password, $this->salt);
        }
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id){
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null){
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username){
        return static::findOne(['username' => $username, 'status' => self::STATUS_NORMAL]);
    }

    /**
     * Finds user by password reset token
     *
     * @param  string      $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token){
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
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
    public function getId(){
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey(){
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey){
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password){
        return Security::validatePassword($password, $this->password);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password){
        $this->password = Security::generatePasswordHash($password,20);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey(){
        $this->auth_key = Security::generateRandomKey();
    }

}
