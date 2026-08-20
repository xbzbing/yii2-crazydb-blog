<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Console\InitMigrateCommand;
use App\Tests\TestCase;
use App\Visit\VisitKeys;
use App\Visit\VisitService;
use Symfony\Component\Console\Tester\CommandTester;

final class VisitServiceTest extends TestCase
{
    private static bool $migrated = false;

    private InMemoryRedisStub $redis;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$migrated) {
            // 确保测试库已应用 V2 列（visit_daily.ip 等，trend 查询需要）
            (new CommandTester(new InitMigrateCommand()))->execute([]);
            self::$migrated = true;
        }
        $this->redis = new InMemoryRedisStub();
    }

    public function testTodayReadsRealtimeBuckets(): void
    {
        $ymd = date('Ymd');
        $this->redis->store[VisitKeys::pvKey($ymd)] = '10';
        $this->redis->pfadd(VisitKeys::uvKey($ymd), ['dev-1', 'dev-2', 'dev-3']);
        $this->redis->pfadd(VisitKeys::ipKey($ymd), ['1.2.3.4', '5.6.7.8']);
        $this->redis->store[VisitKeys::crawlerKey($ymd)] = '2';
        $this->redis->store[VisitKeys::scriptKey($ymd)] = '1';

        $today = (new VisitService($this->redis))->today();

        $this->assertSame(10, $today['pv']);
        $this->assertSame(3, $today['uv']);
        $this->assertSame(2, $today['ip']);
        $this->assertSame(2, $today['pv_crawler']);
        $this->assertSame(1, $today['pv_script']);
        $this->assertSame(7, $today['pv_normal']); // 10 - 2 - 1
    }

    public function testTodayDegradesToZerosOnRedisFailure(): void
    {
        $today = (new VisitService(new FailingRedisStub()))->today();

        $this->assertSame(['pv' => 0, 'uv' => 0, 'ip' => 0, 'pv_crawler' => 0, 'pv_script' => 0, 'pv_normal' => 0], $today);
    }

    public function testHourlyReturnsCompleteHoursOldestFirst(): void
    {
        // 预置最近 3 个小时桶（含不同数值）
        for ($i = 1; $i <= 3; $i++) {
            $ts = strtotime('-' . $i . ' hours');
            $hKey = date('YmdH', $ts);
            $this->redis->store[VisitKeys::pvHourKey($hKey)] = (string)(10 * $i);
            $this->redis->pfadd(VisitKeys::uvHourKey($hKey), ['dev-' . $i]);
            $this->redis->pfadd(VisitKeys::ipHourKey($hKey), ['1.2.3.' . $i]);
        }

        $hourly = (new VisitService($this->redis))->hourly(3);

        $this->assertCount(3, $hourly);
        // 正序：从旧到新（hourly[0] 为 -3h 桶，hourly[2] 为 -1h 桶）
        $this->assertSame(date('Y-m-d H:00', strtotime('-3 hours')), $hourly[0]['time']);
        $this->assertSame(30, $hourly[0]['pv']);
        $this->assertSame(1, $hourly[0]['uv']);
        $this->assertSame(1, $hourly[0]['ip']);
        $this->assertSame(date('Y-m-d H:00', strtotime('-1 hours')), $hourly[2]['time']);
        $this->assertSame(10, $hourly[2]['pv']);
    }

    public function testHourlyDegradesMissingBucketsToZero(): void
    {
        $hourly = (new VisitService($this->redis))->hourly(2);

        $this->assertCount(2, $hourly);
        foreach ($hourly as $point) {
            $this->assertSame(0, $point['pv']);
            $this->assertSame(0, $point['uv']);
            $this->assertSame(0, $point['ip']);
        }
    }

    public function testTrendClampsDaysAndReturnsSeries(): void
    {
        $ymd = date('Ymd');
        $this->redis->store[VisitKeys::pvKey($ymd)] = '6';
        $this->redis->pfadd(VisitKeys::uvKey($ymd), ['dev-x']);
        $this->redis->pfadd(VisitKeys::ipKey($ymd), ['9.9.9.9']);

        // days 超范围钳制到 [1,90]
        $trend = (new VisitService($this->redis))->trend(300);
        $this->assertCount(90, $trend);
        // 今天来自 Redis 实时数据
        $last = $trend[count($trend) - 1];
        $this->assertSame(date('Y-m-d'), $last['date']);
        $this->assertSame(6, $last['pv']);
        $this->assertSame(1, $last['uv']);
        $this->assertSame(1, $last['ip']);
    }

    public function testTrendFallsBackToDatabaseWhenRedisEmpty(): void
    {
        // 数据库有历史数据、Redis 无该日数据时回退 MySQL
        $service = new VisitService($this->redis);
        $ymd = date('Ymd', strtotime('-1 days'));
        $date = date('Y-m-d', strtotime('-1 days'));

        $row = $service->trend(2);
        $this->assertCount(2, $row);
        // 昨天 Redis 无数据且 DB 无数据 → 全 0 但不抛错
        $this->assertSame(0, $row[0]['pv']);
        $this->assertSame($date, $row[0]['date']);
    }
}