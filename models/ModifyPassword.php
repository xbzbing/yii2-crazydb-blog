<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;

/**
 * LoginForm is the model behind the login form.
 */
class ModifyPassword extends Model
{

    public $password;
    public $password_repeat;
    public $old_password;

    private $_user;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['password', 'password_repeat', 'old_password'], 'required'],
            [['password', 'password_repeat'], 'string', 'min' => 8, 'max' => 20],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'operator' => '===', 'message' => '两次密码输入不一致。'],
            ['old_password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     */
    public function validatePassword()
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->old_password)) {
                $this->addError('old_password', '旧密码错误!');
            }
        }
    }

    /**
     * 验证旧密码并保存新密码
     * @return bool
     */
    public function save()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $user->password = $this->password;
            if ($user->save()) {
                Log::record('modify-password', 'user/modify-password', Url::current(), Log::STATUS_SUCCESS, "「{$user->username}」修改密码成功.");
                return true;
            } else {
                Log::record('modify-password', 'user/modify-password', Url::current(), Log::STATUS_FAILED, "「{$user->username}」修改密码失败!");
                $this->errors += $user->errors;
                return false;
            }
        } else {
            return false;
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'User ID',
            'password' => '密码',
            'password_repeat' => '确认密码',
            'old_password' => '旧密码'
        ];
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        if (empty($this->_user)) {
            $this->_user = User::findOne(Yii::$app->user->id);
            $this->_user->setScenario(User::SCENARIO_MODIFY_PWD);
        }

        return $this->_user;
    }
}
