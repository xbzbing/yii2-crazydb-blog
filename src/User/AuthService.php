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
        private string $sessionKey = 'authUserId',
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
        $this->session->regenerateId();
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

    public function isLoggedIn(): bool
    {
        return $this->currentUser() !== null;
    }
}
