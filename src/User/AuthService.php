<?php

declare(strict_types=1);

namespace App\User;

use Yiisoft\Session\SessionInterface;

/**
 * 登录/注销服务：session 登录态 + 记住我 token 生成。
 * （记住我 cookie 的 HTTP 写入/清除在阶段 E/F 与中间件链一起装配。）
 */
final class AuthService
{
    public function __construct(
        private SessionInterface $session,
        private UserRepository $userRepository,
        private string $sessionKey = User::SESSION_AUTH_KEY,
    ) {
    }

    /**
     * 登录：写入 session；rememberMe 时返回记住我 token（由调用方写入 cookie）。
     * 对齐 Yii2 LoginForm：被禁用/已删除/未激活用户拒绝登录。
     */
    public function login(User $user, bool $rememberMe = false): ?string
    {
        if (!$user->isNormal()) {
            throw new \App\Common\CMSException('该用户被禁用!');
        }
        $this->session->set($this->sessionKey, (string)$user->id);
        $this->session->regenerateId();
        $user->touch();
        $user->save();
        return $rememberMe ? $this->userRepository->createRememberMeToken($user) : null;
    }

    public function logout(): void
    {
        $this->session->remove($this->sessionKey);
        // 清理 OTP setup 临时状态（未 enable 就登出时，防止下一登录者继承上一位的密钥）
        $this->session->remove(UserTotpService::SESSION_SETUP_SECRET);
        $this->session->remove(UserTotpService::SESSION_SETUP_URI);
        $this->session->regenerateId();
    }

    /**
     * 修改密码：校验旧密码 → 更新 bcrypt → 轮换 auth_key（旧记住我 token 失效）。
     *
     * @return string|null 错误信息（null = 成功）
     */
    public function changePassword(User $user, string $oldPassword, string $newPassword): ?string
    {
        if (!$user->validatePassword($oldPassword)) {
            return '旧密码不正确。';
        }
        $length = mb_strlen($newPassword);
        if ($length < RegisterService::PASSWORD_MIN || $length > RegisterService::PASSWORD_MAX) {
            return '新密码长度需在 ' . RegisterService::PASSWORD_MIN . ' 到 ' . RegisterService::PASSWORD_MAX . ' 个字符之间。';
        }
        $user->password = User::hashPassword($newPassword);
        // 轮换 auth_key：使已签发的记住我 cookie 全部失效（撤销能力）
        $user->generateAuthKey();
        $user->touch();
        try {
            $user->save();
        } catch (\Throwable) {
            return '保存失败，请稍后再试。';
        }
        return null;
    }

    public function currentUser(): ?User
    {
        $id = $this->session->get($this->sessionKey);
        if (!is_string($id) || $id === '') {
            return null;
        }
        $user = $this->userRepository->findIdentity($id);
        return $user instanceof User ? $user : null;
    }

    /**
     * 修改个人资料（对齐 Yii2 SCENARIO_MODIFY_PROFILE：nickname/email/website/info，
     * 用户名与角色不可改）。
     *
     * @param array{nickname?: string, email?: string, website?: string, info?: string} $data
     * @return array<string, string> 字段 => 错误信息（空 = 成功）
     */
    public function updateProfile(User $user, array $data): array
    {
        $nickname = trim((string)($data['nickname'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $website = trim((string)($data['website'] ?? ''));
        $info = trim((string)($data['info'] ?? ''));

        $errors = [];
        if ($nickname === '') {
            $errors['nickname'] = '昵称不能为空。';
        } elseif (mb_strlen($nickname) > 80) {
            $errors['nickname'] = '昵称最多 80 个字符。';
        } elseif (in_array($nickname, User::NAME_BLACKLIST, true)) {
            $errors['nickname'] = '该昵称不可使用。';
        } elseif (User::query()->where(['nickname' => $nickname])->andWhere(['!=', 'id', (int)$user->id])->exists()) {
            $errors['nickname'] = '昵称已存在，请重新输入。';
        }

        if ($email === '') {
            $errors['email'] = '电子邮箱不能为空。';
        } elseif (mb_strlen($email) > 100) {
            $errors['email'] = '电子邮箱过长。';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = '邮箱格式不正确。';
        } elseif (User::query()->where(['email' => $email])->andWhere(['!=', 'id', (int)$user->id])->exists()) {
            $errors['email'] = '该邮箱已被其他账号使用。';
        }

        if ($website !== '') {
            if (mb_strlen($website) > 100) {
                $errors['website'] = '个人网站地址过长。';
            } elseif (!in_array(strtolower((string)parse_url($website, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                $errors['website'] = '个人网站地址不合法，需要以http或https开头。';
            } elseif (!filter_var($website, FILTER_VALIDATE_URL)) {
                $errors['website'] = '个人网站地址格式不正确。';
            }
        }
        if (mb_strlen($info) > 1000) {
            $errors['info'] = '个人简介过长（最多 1000 字符）。';
        }

        if ($errors !== []) {
            return $errors;
        }

        $user->nickname = $nickname;
        $user->email = $email;
        $user->website = $website === '' ? null : rtrim($website, "/\\\t\n\r\0 \x0B");
        $user->info = $info === '' ? null : \App\Common\XUtils::htmlPurify($info, ['HTML.ForbiddenElements' => ['a']]);
        $user->touch();
        try {
            $user->save();
        } catch (\Throwable) {
            // 并发唯一键冲突（nickname/email，username 不可改）：映射为表单错误
            return ['nickname' => '昵称或邮箱已被其他账号使用，请重新输入。'];
        }
        return [];
    }

    public function isLoggedIn(): bool
    {
        return $this->currentUser() !== null;
    }
}
