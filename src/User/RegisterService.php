<?php

declare(strict_types=1);

namespace App\User;

/**
 * 注册服务：校验 → 创建用户（bcrypt 密码 + 默认值 + 黑名单）。
 * 注册开关（option allow_register）由调用方（控制器）检查。
 */
final class RegisterService
{
    public const PASSWORD_MIN = 8;
    public const PASSWORD_MAX = 20;

    /**
     * @param array{username?:string, nickname?:string, email?:string, password?:string, password_repeat?:string, website?:string, info?:string} $data
     * @return array{user: ?User, errors: array<string, string>}
     */
    public function register(array $data, string $registerIp = '0.0.0.0'): array
    {
        $username = trim((string)($data['username'] ?? ''));
        $nickname = trim((string)($data['nickname'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $passwordRepeat = (string)($data['password_repeat'] ?? '');
        $website = trim((string)($data['website'] ?? ''));
        $info = trim((string)($data['info'] ?? ''));

        $errors = $this->validate($username, $nickname, $email, $password, $passwordRepeat, $website);
        if ($errors !== []) {
            return ['user' => null, 'errors' => $errors];
        }

        $user = new User();
        $user->username = $username;
        $user->nickname = $nickname;
        $user->email = $email;
        $user->website = $website === '' ? null : rtrim($website, "/\\\t\n\r\0 \x0B");
        $user->info = $info === '' ? null : $info;
        $user->password = $password;
        $user->role = User::ROLE_MEMBER;
        $user->status = User::STATUS_NORMAL;
        $user->fillDefaultsForInsert($registerIp);
        $user->save();

        return ['user' => $user, 'errors' => []];
    }

    /**
     * @return array<string, string> 字段 => 错误信息
     */
    private function validate(
        string $username,
        string $nickname,
        string $email,
        string $password,
        string $passwordRepeat,
        string $website,
    ): array {
        $errors = [];

        if ($username === '') {
            $errors['username'] = '用户名不能为空。';
        } elseif (mb_strlen($username) > 20) {
            $errors['username'] = '用户名最多 20 个字符。';
        } elseif (in_array($username, User::NAME_BLACKLIST, true)) {
            $errors['username'] = '该用户名不能被注册！';
        } elseif (User::findByUsername($username) !== null) {
            $errors['username'] = '用户名 已经存在，请重新输入。';
        }

        if ($nickname === '') {
            $errors['nickname'] = '昵称不能为空。';
        } elseif (mb_strlen($nickname) > 80) {
            $errors['nickname'] = '昵称最多 80 个字符。';
        } elseif (in_array($nickname, User::NAME_BLACKLIST, true)) {
            $errors['nickname'] = '该昵称不能被注册！';
        }

        if ($email === '') {
            $errors['email'] = '电子邮箱不能为空。';
        } elseif (mb_strlen($email) > 100) {
            $errors['email'] = '电子邮箱过长。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '邮箱格式不正确';
        } elseif (User::query()->where(['email' => $email])->exists()) {
            $errors['email'] = '电子邮箱 已经存在，请重新输入。';
        }

        $passwordLength = mb_strlen($password);
        if ($password === '') {
            $errors['password'] = '密码不能为空。';
        } elseif ($passwordLength < self::PASSWORD_MIN || $passwordLength > self::PASSWORD_MAX) {
            $errors['password'] = "密码长度需在 " . self::PASSWORD_MIN . " 到 " . self::PASSWORD_MAX . " 个字符之间。";
        } elseif ($passwordRepeat !== $password) {
            $errors['password_repeat'] = '两次密码输入不一致。';
        }

        if ($website !== '' && mb_strlen($website) > 100) {
            $errors['website'] = '个人网站地址过长。';
        } elseif ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            $errors['website'] = '个人网站地址格式不正确。';
        }

        return $errors;
    }
}
