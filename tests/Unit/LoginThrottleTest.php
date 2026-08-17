<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\User\LoginThrottle;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Yiisoft\Cache\CacheInterface;

#[AllowMockObjectsWithoutExpectations]
final class LoginThrottleTest extends TestCase
{
    public function testLocksOnlyTheFailingUsernameAndClientIpPair(): void
    {
        $values = [];
        $psrCache = $this->createMock(SimpleCacheInterface::class);
        $psrCache->method('get')->willReturnCallback(
            static function (string $key, mixed $default = null) use (&$values): mixed {
                return $values[$key] ?? $default;
            },
        );
        $psrCache->method('set')->willReturnCallback(
            static function (string $key, mixed $value, mixed $ttl = null) use (&$values): bool {
                $values[$key] = $value;
                return true;
            },
        );
        $psrCache->method('delete')->willReturnCallback(
            static function (string $key) use (&$values): bool {
                unset($values[$key]);
                return true;
            },
        );
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('psr')->willReturn($psrCache);

        $throttle = new LoginThrottle($cache);
        for ($i = 0; $i < LoginThrottle::MAX_FAILURES; $i++) {
            $throttle->recordFailure('alice', '198.51.100.10');
        }

        self::assertGreaterThan(0, $throttle->remaining('alice', '198.51.100.10'));
        self::assertSame(0, $throttle->remaining('alice', '198.51.100.11'));
        self::assertSame(0, $throttle->remaining('bob', '198.51.100.10'));
        self::assertSame(0, $throttle->remaining('alice', '198.51.100.10', 'post_password'));

        $throttle->clear('alice', '198.51.100.10');
        self::assertSame(0, $throttle->remaining('alice', '198.51.100.10'));
    }
}
