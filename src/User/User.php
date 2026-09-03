<?php

declare(strict_types=1);

namespace App\User;

use Yiisoft\ActiveRecord\ActiveRecord;
use Yiisoft\Auth\IdentityInterface;

final class User extends ActiveRecord implements IdentityInterface
{
    public const STATUS_NORMAL = 1;
    public const STATUS_INACTIVE = 2;
    public const STATUS_BANED = 4;
    public const STATUS_DELETED = 8;

    public const ROLE_MEMBER = 1;
    public const ROLE_EDITOR = 8;
    public const ROLE_ADMIN = 16;

    /** Session 登录态 key（AuthService/SessionAuthMethod/RememberMeMiddleware 共用） */
    public const SESSION_AUTH_KEY = 'authUserId';

    /** 注册黑名单（用户名/昵称不可用：站长保护名 + 管理类称呼） */
    public const NAME_BLACKLIST = ['admin', 'root', 'administrator', '管理员', '站长', '超级管理员'];

    /** 站长账号保护名单（不可被禁用/删除，需与 NAME_BLACKLIST 保持同步） */
    public const WEBMASTER_NAMES = ['admin', 'root'];

    public ?int $id = null;
    public string $nickname = '';
    public string $username = '';
    public ?string $avatar = null;
    public string $password = '';
    public string $email = '';
    public ?string $website = null;
    public int $role = 1;
    public string $register_ip = '';
    public int $register_time = 0;
    public int $update_time = 0;
    public int $active_time = 0;
    public string $auth_key = '';
    public int $status = 1;
    public ?string $otp_secret = null;
    public int $otp_enabled = 0;
    public ?string $info = null;
    public ?string $ext = null;

    public function tableName(): string
    {
        return '{{%user}}';
    }

    public function getId(): ?string
    {
        return $this->id === null ? null : (string)$this->id;
    }

    /**
     * @return array<int, string>
     */
    public static function getAvailableRole(): array
    {
        return [
            self::ROLE_MEMBER => '会员',
            self::ROLE_EDITOR => '编辑',
            self::ROLE_ADMIN => '管理员',
        ];
    }

    public static function getRoleName(int $role): ?string
    {
        return self::getAvailableRole()[$role] ?? null;
    }

    public function getUserRole(): string
    {
        return self::getAvailableRole()[$this->role] ?? '异常角色';
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * 站长账号：管理员角色且在保护名单内（如 admin/root），后台不可被禁用/删除。
     */
    public function isWebmaster(): bool
    {
        return $this->isAdmin() && in_array($this->username, self::WEBMASTER_NAMES, true);
    }

    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    /**
     * @return array<int, string>
     */
    public static function getAvailableStatus(): array
    {
        return [
            self::STATUS_NORMAL => '正常',
            self::STATUS_INACTIVE => '未激活',
            self::STATUS_BANED => '账号被禁用',
            self::STATUS_DELETED => '已删除',
        ];
    }

    public static function getStatusName(int $status): ?string
    {
        return self::getAvailableStatus()[$status] ?? null;
    }

    public function getUserStatus(): string
    {
        return self::getAvailableStatus()[$this->status] ?? '异常状态';
    }

    public function isNormal(): bool
    {
        return $this->status === self::STATUS_NORMAL;
    }

    public function isBaned(): bool
    {
        return $this->status === self::STATUS_BANED;
    }

    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public static function findByUsername(string $username): ?self
    {
        $user = self::query()->where(['username' => $username])->one();
        return $user instanceof self ? $user : null;
    }

    /**
     * 校验密码（bcrypt，兼容 Yii2 存量 hash）。
     */
    public function validatePassword(string $password): bool
    {
        return $this->password !== '' && password_verify($password, $this->password);
    }

    /**
     * 在已成功校验密码后升级过时 hash，调用方负责持久化。
     */
    public function rehashPasswordIfNeeded(string $password): bool
    {
        if (!$this->validatePassword($password) || !password_needs_rehash($this->password, PASSWORD_DEFAULT)) {
            return false;
        }

        $this->password = self::hashPassword($password);
        return true;
    }

    /**
     * 生成 bcrypt 密码 hash。
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 生成"记住我"auth key（base64，32 字节随机）。
     */
    public function generateAuthKey(): void
    {
        $this->auth_key = base64_encode(random_bytes(32));
    }

    /**
     * 新建用户时填充时间戳/注册 IP/auth_key（等价 Yii2 User::beforeSave 的新记录逻辑）。
     * 不覆盖调用方已设置的 role/status；业务默认值由调用方（如 RegisterService）显式设置。
     */
    public function fillDefaultsForInsert(string $registerIp = '0.0.0.0'): void
    {
        $now = time();
        $this->register_time = $now;
        $this->update_time = $now;
        $this->active_time = $now;
        $this->register_ip = $registerIp;
        if ($this->auth_key === '') {
            $this->generateAuthKey();
        }
        if ($this->password !== '' && !$this->isHashed()) {
            $this->password = self::hashPassword($this->password);
        }
    }

    /**
     * 修改资料/登录活跃时更新时间戳。
     */
    public function touch(): void
    {
        $this->update_time = time();
        $this->active_time = time();
    }

    /**
     * 判断密码字段是否已是 bcrypt hash（完整格式校验，避免普通密码恰好以 $2y$ 开头被误判）。
     */
    private function isHashed(): bool
    {
        return preg_match('/^\$2[ayb]\$\d{2}\$[.\/A-Za-z0-9]{53}$/', $this->password) === 1;
    }
}
