<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\helpers\HtmlPurifier;
use yii\web\IdentityInterface;
use app\components\XUtils;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property string $id
 * @property string $nickname
 * @property string $username
 * @property string $avatar
 * @property string $password
 * @property string $email
 * @property string $website
 * @property integer $role
 * @property string $register_ip
 * @property integer $register_time
 * @property integer $update_time
 * @property string $auth_key
 * @property integer $status
 * @property string $info
 * @property string $ext
 * @property integer active_time
 *
 * #getter
 * @property string $userStatus
 * @property string $userRole
 *
 * @property Comment[] $comments
 * @property Comment[] $allComments
 * @property Post[] $posts
 * @property Post[] $allPosts
 */
class User extends ActiveRecord implements IdentityInterface
{

    const STATUS_NORMAL = 1;
    const STATUS_INACTIVE = 2;
    const STATUS_BANED = 4;
    const STATUS_DELETED = 8;

    const SCENARIO_REGISTER = 'register';
    const SCENARIO_MODIFY_PROFILE = 'modify_profile';
    const SCENARIO_MODIFY_PWD = 'modify_password';
    const SCENARIO_MANAGE = 'manage';

    const ROLE_MEMBER = 1;
    const ROLE_EDITOR = 8;
    const ROLE_ADMIN = 16;

    /**
     * 黑名单
     * @var string[]
     */
    public $nameBlackList = ['admin'];

    public $password_repeat;

    public $old_password;

    public $captcha;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = ['username', 'nickname', 'password', 'info', 'email', 'avatar', 'password_repeat', 'captcha'];
        $scenarios[self::SCENARIO_MODIFY_PROFILE] = ['nickname', 'password', 'info', 'email', 'avatar'];
        $scenarios[self::SCENARIO_MODIFY_PWD] = ['password', 'old_password', 'password_repeat'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'email', 'nickname'], 'required'],
            [['status'], 'default', 'value' => self::STATUS_NORMAL],
            [['status'], 'in', 'range' => array_keys(self::getAvailableStatus())],
            [['info'], 'string'],
            [['avatar'], 'string', 'max' => 255],
            [['nickname'], 'string', 'max' => 80],
            [['username'], 'string', 'max' => 20],
            [['password'], 'string', 'max' => 60],
            [['website'], 'url'],
            [['password', 'password_repeat'], 'string', 'min' => '8', 'max' => 20, 'on' => [self::SCENARIO_REGISTER, self::SCENARIO_MODIFY_PWD], 'message' => '{attribute} 需要在8-20位之间。'],
            [['password', 'password_repeat'], 'required', 'on' => [self::SCENARIO_REGISTER, self::SCENARIO_MODIFY_PWD]],
            [['email', 'website', 'role'], 'string', 'max' => 100],
            [['email'], 'email', 'message' => '邮箱格式不正确'],
            [['username', 'nickname', 'email'], 'unique', 'message' => '{attribute} 已经存在，请重新输入。'],
            ['old_password', 'required', 'on' => self::SCENARIO_MODIFY_PWD],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'operator' => '===', 'message' => '两次密码输入不一致。'],
            ['captcha', 'captcha', 'skipOnEmpty' => false, 'on' => self::SCENARIO_REGISTER],
        ];
    }

    /**
     * 获得支持的所有角色
     * @return array
     */
    public static function getAvailableRole()
    {
        return [
            self::ROLE_MEMBER => '会员',
            self::ROLE_EDITOR => '编辑',
            self::ROLE_ADMIN => '管理员'
        ];
    }

    /**
     * 获得用户角色对应的名称
     * @param $role
     * @return null|string
     */
    public static function getRoleName($role)
    {
        $items = self::getAvailableRole();
        return isset($items[$role]) ? $items[$role] : null;
    }

    public function getUserRole()
    {
        $items = self::getAvailableRole();
        if (isset($items[$this->role]))
            return $items[$this->role];
        else
            return '异常角色';
    }

    /**
     * 是否是管理员
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role == self::ROLE_ADMIN;
    }

    /**
     * 是否是会员
     * @return bool
     */
    public function isMember()
    {
        return $this->role == self::ROLE_MEMBER;
    }

    /**
     * 是否是编辑
     * @return bool
     */
    public function isEditor()
    {
        return $this->role == self::ROLE_EDITOR;
    }

    /**
     * 获得支持的所有状态值
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
            return '异常状态';
    }

    /**
     * 用户状态是否正常
     * @return bool
     */
    public function isNormal()
    {
        return $this->status == self::STATUS_NORMAL;
    }

    /**
     * 用户是否被禁止登录
     * @return bool
     */
    public function isBaned()
    {
        return $this->status == self::STATUS_BANED;
    }

    /**
     * 用户是否被删除
     * @return bool
     */
    public function isDeleted()
    {
        return $this->status == self::STATUS_DELETED;
    }

    /**
     * 用户是否是未激活状态
     * @return bool
     */
    public function isInactive()
    {
        return $this->status == self::STATUS_INACTIVE;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {

        return [
            'id' => '用户 ID',
            'nickname' => '昵称',
            'username' => '用户名',
            'password' => '密码',
            'avatar' => '头像',
            'email' => '电子邮箱',
            'website' => '个人网站',
            'role' => '用户角色',
            'register_ip' => '注册 IP',
            'register_time' => '注册时间',
            'update_time' => '更新时间',
            'auth_key' => '授权代码',
            'status' => '用户状态',
            'info' => '个人简介',
            'ext' => '保留字段',
            'userStatus' => '用户状态',
            'userRole' => '用户角色',
            'active_time' => '活动时间',
            'password_repeat' => '确认密码',
            'old_password' => '旧密码'
        ];
    }

    /**
     * 保存前校验补充数据
     * @param bool $insert
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert))
            return false;
        if ($this->isNewRecord) {
            $this->register_time = time();
            $this->register_ip = XUtils::getClientIP();
            $this->generateAuthKey();
        }
        //注册黑名单
        if (in_array($this->username, $this->nameBlackList) || in_array($this->nickname, $this->nameBlackList)) {
            $this->$this->addError('username', '该用户名不能被注册！');
        }
        if (in_array($this->scenario, array(self::SCENARIO_REGISTER, self::SCENARIO_MODIFY_PWD))) {
            $this->password = $this->hashPassword($this->password);
        }

        $this->info = HtmlPurifier::process($this->info, ['HTML.ForbiddenElements' => ['a']]);

        //管理员更改用户数据不修改活动时间
        if ($this->scenario !== self::SCENARIO_MANAGE)
            $this->active_time = time();

        $this->update_time = time();
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
        $this->auth_key = base64_encode(Yii::$app->security->generateRandomKey());
    }

    public function getPosts()
    {
        return $this->hasMany(Post::className(), ['author_id' => 'id'])
            ->onCondition(['status' => [Post::STATUS_HIDDEN, Post::STATUS_PUBLISHED]])
            ->orderBy(['create_time' => SORT_DESC]);
    }

    public function getAllPosts()
    {
        return $this->hasMany(Post::className(), ['author_id' => 'id'])->orderBy(['is_top' => SORT_DESC, 'post_time' => SORT_DESC]);
    }

    public function getComments()
    {
        return $this->hasMany(Comment::className(), ['uid' => 'id'])
            ->onCondition(['status' => Comment::STATUS_APPROVED])
            ->orderBy(['create_time' => SORT_DESC]);
    }

    public function getAllComments()
    {
        return $this->hasMany(Comment::className(), ['uid' => 'id'])->orderBy(['is_top' => SORT_DESC, 'post_time' => SORT_DESC]);
    }

}
