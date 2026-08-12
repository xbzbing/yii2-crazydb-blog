<?php

declare(strict_types=1);

namespace App\User;

use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;

final class UserRepository implements IdentityRepositoryInterface, IdentityWithTokenRepositoryInterface
{
    /**
     * 记住我 token 格式：base64_encode("{id}:{auth_key}")。
     */
    public function createRememberMeToken(User $user): string
    {
        return base64_encode($user->id . ':' . $user->auth_key);
    }

    public function findIdentity(string $id): ?IdentityInterface
    {
        $user = User::query()->findByPk((int)$id);
        if (!$user instanceof User || !$user->isNormal()) {
            return null;
        }
        return $user;
    }

    public function findIdentityByToken(string $token, ?string $type = null): ?IdentityInterface
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }
        [$id, $authKey] = explode(':', $decoded, 2);
        if (!ctype_digit($id)) {
            return null;
        }
        $user = User::query()->findByPk((int)$id);
        if (!$user instanceof User || !$user->isNormal()) {
            return null;
        }
        if ($user->auth_key === '' || !hash_equals($user->auth_key, (string)$authKey)) {
            return null;
        }
        return $user;
    }
}
