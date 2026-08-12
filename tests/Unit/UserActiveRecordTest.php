<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use App\User\User;

/**
 * Requires the MySQL container (blog-mysql) with the seeded crazydb database.
 * Uses a throwaway user record so no existing data is modified.
 */
final class UserActiveRecordTest extends TestCase
{
    private const USERNAME_PREFIX = '__yii3_';

    private string $username;

    protected function setUp(): void
    {
        parent::setUp();
        $this->username = self::USERNAME_PREFIX . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        User::query()->where(['username' => $this->username])->one()?->delete();
    }

    public function testReadExistingRecord(): void
    {
        $user = User::query()->findByPk(1);

        self::assertNotNull($user);
        self::assertSame('dabing', $user->username);
        self::assertSame('管理员', $user->nickname);
    }

    public function testCreateAndUpdateAndDelete(): void
    {
        $user = new User();
        $user->username = $this->username;
        $user->nickname = 'ar-test';
        $user->email = $this->username . '@example.com';
        $user->password = 'hash';
        $user->save();

        $id = $user->id;
        self::assertNotNull($id);

        $loaded = User::query()->findByPk($id);
        self::assertNotNull($loaded);
        self::assertSame('ar-test', $loaded->nickname);

        $loaded->nickname = 'ar-test-updated';
        $loaded->save();

        $reloaded = User::query()->findByPk($id);
        self::assertSame('ar-test-updated', $reloaded?->nickname);
    }
}
