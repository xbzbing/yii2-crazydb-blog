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

    /** 测试目标日期（昨天）：须落在 VisitSyncService 的同步保留窗口内，且避开今天的实时数据 */
    private string $testDate;

    private string $testYmd;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            // 确保测试库已应用 V2 列（visit_daily.ip 等），幂等执行
            (new CommandTester(new InitMigrateCommand()))->execute([]);
            self::$migrated = true;
        }
        // 固定硬编码日期会随日期漂移失效（固定窗口只覆盖最近 30 天）；用昨天保证稳定
        $this->testDate = date('Y-m-d', strtotime('-1 day'));
        $this->testYmd = str_replace('-', '', $this->testDate);
        $this->redis = new InMemoryRedisStub();
        // 模拟生产写入侧：中间件会在每次访问时把当日登记进日期索引集
        $this->redis->sadd(VisitKeys::datesKey(), [$this->testYmd]);
    }

    protected function tearDown(): void
    {
        // 清理测试写入的 visit_daily 数据
        (new VisitDaily())->deleteAll(['date' => $this->testDate]);
        parent::tearDown();
    }

    public function testSyncsIncrementalPvAndFullUvIpToDatabase(): void
    {
        $ymd = $this->testYmd;
        $this->redis->store[VisitKeys::pvKey($ymd)] = '10';
        $this->redis->pfadd(VisitKeys::uvKey($ymd), ['dev-1', 'dev-2', 'dev-3']);
        $this->redis->pfadd(VisitKeys::ipKey($ymd), ['1.2.3.4', '5.6.7.8']);
        $this->redis->store[VisitKeys::crawlerKey($ymd)] = '2';

        $exit = $this->runCommand();

        $this->assertSame(ExitCode::OK, $exit);
        $row = VisitDaily::query()->where(['date' => $this->testDate])->one();
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
        $ymd = $this->testYmd;
        $this->redis->store[VisitKeys::pvKey($ymd)] = '10';
        $this->redis->pfadd(VisitKeys::uvKey($ymd), ['dev-1', 'dev-2']);
        $this->redis->pfadd(VisitKeys::ipKey($ymd), ['1.2.3.4']);

        $this->runCommand();
        $this->runCommand();

        $row = VisitDaily::query()->where(['date' => $this->testDate])->one();
        $this->assertInstanceOf(VisitDaily::class, $row);
        // PV 增量重复执行不重复累计
        $this->assertSame(10, (int)$row->pv);
        $this->assertSame(2, (int)$row->uv);
    }

    public function testCleansOldDailyKeysIncludingSyncCursors(): void
    {
        $oldYmd = date('Ymd', strtotime('-40 days'));
        // 该日有流量 → 写入侧会把日期登记进索引集（类似中间件 SADD）
        $this->redis->sadd(VisitKeys::datesKey(), [$oldYmd]);
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