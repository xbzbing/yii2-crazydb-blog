<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use App\User\AuthService;
use App\User\RegisterService;
use App\User\SessionAuthMethod;
use App\User\User;
use App\User\UserRepository;
use Psr\Http\Message\ServerRequestInterface;

final class UserAuthTest extends TestCase
{

    /**
     * @return array{user: User, cleanup: callable}
     */
    private function createUser(string $suffix, array $overrides = []): array
    {
        $user = new User();
        $user->username = 'test_' . $suffix;
        $user->nickname = '测试' . $suffix;
        $user->email = $suffix . '@example.com';
        $user->password = 'password123';
        foreach ($overrides as $key => $value) {
            $user->$key = $value;
        }
        $user->fillDefaultsForInsert('203.0.113.10');
        $user->save();
        return ['user' => $user, 'cleanup' => static fn() => $user->delete()];
    }

    public function testLegacyBcryptHashValidates(): void
    {
        $user = new User();
        $user->password = '$2y$12$fn2WB.kSElGSML.fRjDL6eOPcIycPaY0QFI47tOz62sHLrlTEymb2';
        self::assertTrue($user->validatePassword('password123'));
        self::assertFalse($user->validatePassword('wrong'));
        self::assertFalse($user->validatePassword(''));
    }

    public function testHashPasswordRoundtrip(): void
    {
        $hash = User::hashPassword('secret-pass');
        self::assertTrue(password_verify('secret-pass', $hash));
    }

    public function testRoleAndStatusHelpers(): void
    {
        $user = new User();
        $user->role = User::ROLE_ADMIN;
        $user->status = User::STATUS_BANED;
        self::assertTrue($user->isAdmin());
        self::assertFalse($user->isEditor());
        self::assertFalse($user->isMember());
        self::assertTrue($user->isBaned());
        self::assertFalse($user->isNormal());
        self::assertSame('管理员', $user->getUserRole());
        self::assertSame('账号被禁用', $user->getUserStatus());
        self::assertSame('会员', User::getRoleName(User::ROLE_MEMBER));
        self::assertNull(User::getRoleName(999));
    }

    public function testFindByUsername(): void
    {
        $suffix = 'find_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix);
        try {
            $found = User::findByUsername('test_' . $suffix);
            self::assertNotNull($found);
            self::assertSame('测试' . $suffix, $found->nickname);
            self::assertNull(User::findByUsername('no_such_user_' . $suffix));
        } finally {
            $created['cleanup']();
        }
    }

    public function testFillDefaultsForInsert(): void
    {
        $suffix = 'defaults_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix);
        try {
            $user = $created['user'];
            self::assertSame(User::ROLE_MEMBER, $user->role);
            self::assertSame(User::STATUS_NORMAL, $user->status);
            self::assertGreaterThan(0, $user->register_time);
            self::assertSame('203.0.113.10', $user->register_ip);
            self::assertNotSame('', $user->auth_key);
            self::assertTrue($user->validatePassword('password123'), 'password should be hashed on insert');
            self::assertTrue($user->validatePassword('password123'), 'hashed password stays valid');
        } finally {
            $created['cleanup']();
        }
    }

    public function testAuthKeyGeneration(): void
    {
        $user = new User();
        $user->generateAuthKey();
        self::assertSame(44, strlen($user->auth_key));
        self::assertNotSame($user->auth_key, (static function () use ($user): string {
            $user->generateAuthKey();
            return $user->auth_key;
        })());
    }

    public function testRememberMeTokenRoundtrip(): void
    {
        $suffix = 'token_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix);
        try {
            $repository = new UserRepository();
            $token = $repository->createRememberMeToken($created['user']);
            $identity = $repository->findIdentityByToken($token);
            self::assertInstanceOf(User::class, $identity);
            self::assertSame($created['user']->id, $identity->id);

            self::assertNull($repository->findIdentityByToken($token . 'x'));
            self::assertNull($repository->findIdentityByToken(base64_encode('999999:abc')));
            self::assertNull($repository->findIdentityByToken('not-base64!!'));
        } finally {
            $created['cleanup']();
        }
    }

    public function testRememberMeTokenRejectsTamperedAuthKey(): void
    {
        $suffix = 'tamper_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix);
        try {
            $repository = new UserRepository();
            $user = $created['user'];
            $tampered = base64_encode($user->id . ':' . str_repeat('x', 44));
            self::assertNull($repository->findIdentityByToken($tampered));
        } finally {
            $created['cleanup']();
        }
    }

    public function testRegisterSuccess(): void
    {
        $suffix = 'reg_' . bin2hex(random_bytes(3));
        $service = new RegisterService();
        $result = $service->register([
            'username' => 'newuser_' . $suffix,
            'nickname' => '新用户' . $suffix,
            'email' => 'newuser_' . $suffix . '@example.com',
            'password' => 'password123',
            'password_repeat' => 'password123',
        ], '198.51.100.7');

        self::assertSame([], $result['errors']);
        $user = $result['user'];
        self::assertInstanceOf(User::class, $user);
        try {
            self::assertSame(User::ROLE_MEMBER, $user->role);
            self::assertSame(User::STATUS_NORMAL, $user->status);
            self::assertSame('198.51.100.7', $user->register_ip);
            self::assertTrue($user->validatePassword('password123'));
            self::assertNotNull(User::findByUsername('newuser_' . $suffix));
        } finally {
            $user->delete();
        }
    }

    public function testRegisterRejectsBlacklistedName(): void
    {
        $service = new RegisterService();
        $result = $service->register([
            'username' => 'admin',
            'nickname' => '管理员',
            'email' => 'x' . bin2hex(random_bytes(3)) . '@example.com',
            'password' => 'password123',
            'password_repeat' => 'password123',
        ]);
        self::assertArrayHasKey('username', $result['errors']);
        self::assertNull($result['user']);
    }

    public function testRegisterRejectsDuplicateUsername(): void
    {
        $suffix = 'dup_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix);
        try {
            $service = new RegisterService();
            $result = $service->register([
                'username' => 'test_' . $suffix,
                'nickname' => '另一个昵称',
                'email' => 'other' . bin2hex(random_bytes(3)) . '@example.com',
                'password' => 'password123',
                'password_repeat' => 'password123',
            ]);
            self::assertArrayHasKey('username', $result['errors']);
            self::assertNull($result['user']);
        } finally {
            $created['cleanup']();
        }
    }

    public function testRegisterRejectsWeakPasswordAndMismatch(): void
    {
        $suffix = 'weak_' . bin2hex(random_bytes(3));
        $service = new RegisterService();
        $result = $service->register([
            'username' => 'weakuser_' . $suffix,
            'nickname' => '弱密码用户',
            'email' => 'weak_' . $suffix . '@example.com',
            'password' => 'short',
            'password_repeat' => 'short',
        ]);
        self::assertArrayHasKey('password', $result['errors']);

        $result2 = $service->register([
            'username' => 'weakuser2_' . $suffix,
            'nickname' => '不一致用户',
            'email' => 'weak2_' . $suffix . '@example.com',
            'password' => 'password123',
            'password_repeat' => 'different123',
        ]);
        self::assertArrayHasKey('password_repeat', $result2['errors']);
        self::assertNull($result2['user']);
    }

    public function testAuthServiceLoginLogout(): void
    {
        $suffix = 'auth_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix);
        try {
            $auth = new AuthService($this->sharedSession(), new UserRepository());
            self::assertFalse($auth->isLoggedIn());

            $token = $auth->login($created['user'], true);
            self::assertIsString($token);
            self::assertTrue($auth->isLoggedIn());
            self::assertSame($created['user']->id, $auth->currentUser()?->id);

            $auth->logout();
            self::assertFalse($auth->isLoggedIn());
            self::assertNull($auth->currentUser());
        } finally {
            $created['cleanup']();
        }
    }

    public function testSessionAuthMethodRestoresIdentity(): void
    {
        $suffix = 'sess_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix);
        try {
            $session = $this->sharedSession();
            $method = new SessionAuthMethod($session, new UserRepository());

            $request = $this->createStub(ServerRequestInterface::class);
            self::assertNull($method->authenticate($request));

            $auth = new AuthService($session, new UserRepository());
            $auth->login($created['user']);
            $identity = $method->authenticate($request);
            self::assertInstanceOf(User::class, $identity);
            self::assertSame($created['user']->id, $identity->id);

            $auth->logout();
            self::assertNull($method->authenticate($request));
        } finally {
            $created['cleanup']();
        }
    }

    public function testInactiveUserCannotAuthenticate(): void
    {
        $suffix = 'inactive_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix, ['status' => User::STATUS_INACTIVE]);
        try {
            $repository = new UserRepository();
            self::assertNull($repository->findIdentity((string)$created['user']->id));
            self::assertNull($repository->findIdentityByToken($repository->createRememberMeToken($created['user'])));
        } finally {
            $created['cleanup']();
        }
    }

    public function testDeletedUserCannotAuthenticate(): void
    {
        $suffix = 'deleted_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix, ['status' => User::STATUS_DELETED]);
        try {
            $repository = new UserRepository();
            self::assertNull($repository->findIdentity((string)$created['user']->id));
            self::assertNull($repository->findIdentityByToken($repository->createRememberMeToken($created['user'])));
        } finally {
            $created['cleanup']();
        }
    }

    public function testBanedUserCannotAuthenticate(): void
    {
        $suffix = 'baned_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix, ['status' => User::STATUS_BANED]);
        try {
            $repository = new UserRepository();
            self::assertNull($repository->findIdentity((string)$created['user']->id));
            self::assertNull($repository->findIdentityByToken($repository->createRememberMeToken($created['user'])));
        } finally {
            $created['cleanup']();
        }
    }

    public function testBanedUserCannotLogin(): void
    {
        $suffix = 'banlogin_' . bin2hex(random_bytes(3));
        $created = $this->createUser($suffix, ['status' => User::STATUS_BANED]);
        try {
            $auth = new AuthService($this->sharedSession(), new UserRepository());
            $this->expectException(\App\Common\CMSException::class);
            $this->expectExceptionMessage('该用户被禁用!');
            $auth->login($created['user']);
        } finally {
            $created['cleanup']();
        }
    }
}
