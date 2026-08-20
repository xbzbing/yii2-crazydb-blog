<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Console\InitMigrateCommand;
use App\Tests\TestCase;
use App\Visit\VisitDaily;
use App\Visit\VisitKeys;
use App\Visit\VisitSyncTrigger;
use Symfony\Component\Console\Tester\CommandTester;

final class VisitSyncTriggerTest extends TestCase
{
    private static bool $migrated = false;

    private InMemoryRedisStub $redis;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            (new CommandTester(new InitMigrateCommand()))->execute([]);
            self::$migrated = true;
        }
        $this->redis = new InMemoryRedisStub();
    }

    protected function tearDown(): void
    {
        (new VisitDaily())->deleteAll(['date' => date('Y-m-d')]);
        parent::tearDown();
    }

    public function testTriggersSyncOfDailyStats(): void
    {
        $ymd = date('Ymd');
        $this->redis->store[VisitKeys::pvKey($ymd)] = '7';
        $this->redis->sadd(VisitKeys::datesKey(), [$ymd]);

        $this->assertTrue((new VisitSyncTrigger($this->redis))->trigger());

        $row = VisitDaily::query()->where(['date' => date('Y-m-d')])->one();
        $this->assertInstanceOf(VisitDaily::class, $row);
        $this->assertSame(7, (int)$row->pv);
        $this->assertGreaterThan(0, (int)$this->redis->store[VisitSyncTrigger::LAST_SYNC_KEY]);
    }

    public function testDebounceSkipsWithinWindow(): void
    {
        $ymd = date('Ymd');
        $this->redis->store[VisitKeys::pvKey($ymd)] = '7';
        $this->redis->sadd(VisitKeys::datesKey(), [$ymd]);
        // 300s 内刚同步过
        $this->redis->store[VisitSyncTrigger::LAST_SYNC_KEY] = (string)time();

        $this->assertFalse((new VisitSyncTrigger($this->redis))->trigger());

        $this->assertNull(VisitDaily::query()->where(['date' => date('Y-m-d')])->one());
    }

    public function testHeldLockSkipsSync(): void
    {
        $ymd = date('Ymd');
        $this->redis->store[VisitKeys::pvKey($ymd)] = '7';
        $this->redis->sadd(VisitKeys::datesKey(), [$ymd]);
        $this->redis->store['crazydb:lock:visit-sync'] = (string)time();

        $this->assertFalse((new VisitSyncTrigger($this->redis))->trigger());
    }
}