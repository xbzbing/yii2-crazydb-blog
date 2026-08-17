<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\User\User;
use PHPUnit\Framework\TestCase;

final class UserPasswordRehashTest extends TestCase
{
    public function testRehashesAnOutdatedPasswordHashAfterSuccessfulVerification(): void
    {
        $user = new User();
        $user->password = password_hash('correct password', PASSWORD_BCRYPT, ['cost' => 4]);
        $oldHash = $user->password;

        self::assertTrue($user->validatePassword('correct password'));
        self::assertTrue($user->rehashPasswordIfNeeded('correct password'));
        self::assertNotSame($oldHash, $user->password);
        self::assertTrue($user->validatePassword('correct password'));
    }

    public function testDoesNotRehashWhenThePasswordWasNotVerified(): void
    {
        $user = new User();
        $user->password = password_hash('correct password', PASSWORD_BCRYPT, ['cost' => 4]);
        $oldHash = $user->password;

        self::assertFalse($user->validatePassword('wrong password'));
        self::assertFalse($user->rehashPasswordIfNeeded('wrong password'));
        self::assertSame($oldHash, $user->password);
    }
}
