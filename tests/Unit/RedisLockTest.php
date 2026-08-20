<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Common\RedisLock;
use App\Tests\TestCase;

final class RedisLockTest extends TestCase
{
    private InMemoryRedisStub $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = new InMemoryRedisStub();
    }

    public function testAcquireSetsLockWithNx(): void
    {
        $lock = new RedisLock($this->redis);

        $this->assertTrue($lock->acquire('lock:a'));
        $this->assertArrayHasKey('lock:a', $this->redis->store);
        // 已持有 → 获取失败（NX 语义）
        $this->assertFalse($lock->acquire('lock:a'));
    }

    public function testReleaseDeletesLock(): void
    {
        $lock = new RedisLock($this->redis);
        $this->assertTrue($lock->acquire('lock:b'));

        $lock->release('lock:b');

        $this->assertArrayNotHasKey('lock:b', $this->redis->store);
    }

    public function testAcquireFailsOnHeldLock(): void
    {
        // 另一实例已持锁
        $this->redis->store['lock:c'] = (string)time();

        $this->assertFalse((new RedisLock($this->redis))->acquire('lock:c'));
    }

    public function testAcquireDegradesToAllowedWhenRedisDown(): void
    {
        // Redis 不可用 → 放行（统计同步失败静默，不阻塞业务）
        $this->assertTrue((new RedisLock(new FailingRedisStub()))->acquire('lock:d'));
    }

    public function testReleaseIsSilentWhenRedisDown(): void
    {
        // 释放失败静默，不抛异常
        (new RedisLock(new FailingRedisStub()))->release('lock:e');
        $this->addToAssertionCount(1);
    }
}