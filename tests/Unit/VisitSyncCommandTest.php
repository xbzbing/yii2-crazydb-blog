<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Console\InitMigrateCommand;
use App\Console\VisitSyncCommand;
use App\Tests\TestCase;
use App\Visit\VisitDaily;
use App\Visit\VisitKeys;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Yii\Console\ExitCode;

final class VisitSyncCommandTest extends TestCase
{
    private static bool $migrated = false;

    private InMemoryRedisStub $redis;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            // 确保测试库已应用 V2 列（visit_daily.ip 等），幂等执行
            (new CommandTester(new InitMigrateCommand()))->execute([]);
            self::$migrated = true;
        }
        $this->redis = new InMemoryRedisStub();
    }

    protected function tearDown(): void
    {
        // 清理测试写入的 visit_daily 数据
        (new VisitDaily())->deleteAll(['date' => '2026-08-19']);
        parent::tearDown();
    }

    public function testSyncsIncrementalPvAndFullUvIpToDatabase(): void
    {
        $ymd = '20260819';
        $this->redis->store[VisitKeys::pvKey($ymd)] = '10';
        $this->redis->pfadd(VisitKeys::uvKey($ymd), ['dev-1', 'dev-2', 'dev-3']);
        $this->redis->pfadd(VisitKeys::ipKey($ymd), ['1.2.3.4', '5.6.7.8']);
        $this->redis->store[VisitKeys::crawlerKey($ymd)] = '2';

        $exit = $this->runCommand();

        $this->assertSame(ExitCode::OK, $exit);
        $row = VisitDaily::query()->where(['date' => '2026-08-19'])->one();
        $this->assertInstanceOf(VisitDaily::class, $row);
        $this->assertSame(10, (int)$row->pv);
        $this->assertSame(3, (int)$row->uv);
        $this->assertSame(2, (int)$row->ip);
        $this->assertSame(2, (int)$row->pv_crawler);
        // 游标写入，供下次增量计算
        $this->assertSame('10', $this->redis->store[VisitKeys::syncedKey($ymd)]);
    }

    public function testIsIdempotentOnSecondRun(): void
    {
        $ymd = '20260819';
        $this->redis->store[VisitKeys::pvKey($ymd)] = '10';
        $this->redis->pfadd(VisitKeys::uvKey($ymd), ['dev-1', 'dev-2']);
        $this->redis->pfadd(VisitKeys::ipKey($ymd), ['1.2.3.4']);

        $this->runCommand();
        $this->runCommand();

        $row = VisitDaily::query()->where(['date' => '2026-08-19'])->one();
        $this->assertInstanceOf(VisitDaily::class, $row);
        // PV 增量重复执行不重复累计
        $this->assertSame(10, (int)$row->pv);
        $this->assertSame(2, (int)$row->uv);
    }

    public function testCleansOldDailyKeysIncludingSyncCursors(): void
    {
        $oldYmd = date('Ymd', strtotime('-40 days'));
        $this->redis->store[VisitKeys::pvKey($oldYmd)] = '5';
        $this->redis->store[VisitKeys::uvKey($oldYmd)] = ['old-dev'];
        $this->redis->store[VisitKeys::ipKey($oldYmd)] = ['1.2.3.4'];
        $this->redis->store[VisitKeys::crawlerKey($oldYmd)] = '1';
        $this->redis->store[VisitKeys::scriptKey($oldYmd)] = '0';
        $this->redis->store[VisitKeys::syncedKey($oldYmd)] = '5';
        $this->redis->store[VisitKeys::crawlerSyncedKey($oldYmd)] = '1';
        $this->redis->store[VisitKeys::scriptSyncedKey($oldYmd)] = '0';

        $this->runCommand();

        $this->assertArrayNotHasKey(VisitKeys::pvKey($oldYmd), $this->redis->store);
        $this->assertArrayNotHasKey(VisitKeys::uvKey($oldYmd), $this->redis->store);
        $this->assertArrayNotHasKey(VisitKeys::syncedKey($oldYmd), $this->redis->store);
        $this->assertArrayNotHasKey(VisitKeys::crawlerSyncedKey($oldYmd), $this->redis->store);
        $this->assertArrayNotHasKey(VisitKeys::scriptSyncedKey($oldYmd), $this->redis->store);
    }

    private function runCommand(): int
    {
        $tester = new CommandTester(new VisitSyncCommand($this->redis));
        $tester->execute([]);
        return $tester->getStatusCode();
    }
}