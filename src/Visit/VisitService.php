<?php

declare(strict_types=1);

namespace App\Visit;

use Predis\ClientInterface;

/**
 * 访问统计服务：读 Redis 实时 PV/UV/IP，组合 MySQL 历史数据成趋势序列。
 */
final readonly class VisitService
{
    public function __construct(
        private ClientInterface $redis,
    ) {
    }

    /**
     * 今日实时 PV/UV/IP/分类 PV（读 Redis，未同步也实时可见）。
     *
     * @return array{pv: int, uv: int, ip: int, pv_crawler: int, pv_script: int, pv_normal: int}
     */
    public function today(): array
    {
        $ymd = date('Ymd');
        try {
            $pv = $this->countOf(VisitKeys::pvKey($ymd));
            $uv = $this->redis->pfcount(VisitKeys::uvKey($ymd));
            $ip = $this->redis->pfcount(VisitKeys::ipKey($ymd));
            $crawler = $this->countOf(VisitKeys::crawlerKey($ymd));
            $script = $this->countOf(VisitKeys::scriptKey($ymd));
        } catch (\Throwable) {
            $pv = 0;
            $uv = 0;
            $ip = 0;
            $crawler = 0;
            $script = 0;
        }
        return [
            'pv' => $pv,
            'uv' => $uv,
            'ip' => $ip,
            'pv_crawler' => $crawler,
            'pv_script' => $script,
            'pv_normal' => max(0, $pv - $crawler - $script),
        ];
    }

    /**
     * 最近 N 天每日 PV/UV/IP/分类 PV 趋势（Redis 实时数据优先，历史日期从 MySQL 补）。
     *
     * @return list<array{date: string, pv: int, uv: int, ip: int, pv_crawler: int, pv_script: int, pv_normal: int}>
     */
    public function trend(int $days): array
    {
        $days = max(1, min(90, $days));
        $fromTs = strtotime('-' . ($days - 1) . ' days');
        $from = $fromTs === false ? date('Y-m-d') : date('Y-m-d', $fromTs);
        $to = date('Y-m-d');

        $fromDb = VisitDaily::range($from, $to);
        $dbMap = [];
        foreach ($fromDb as $row) {
            $dbMap[$row['date']] = $row;
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $ts = strtotime('-' . $i . ' days');
            $date = $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
            $ymd = str_replace('-', '', $date);
            try {
                $pv = $this->countOf(VisitKeys::pvKey($ymd));
                $uv = $this->redis->pfcount(VisitKeys::uvKey($ymd));
                $ip = $this->redis->pfcount(VisitKeys::ipKey($ymd));
                $crawler = $this->countOf(VisitKeys::crawlerKey($ymd));
                $script = $this->countOf(VisitKeys::scriptKey($ymd));
            } catch (\Throwable) {
                $pv = 0;
                $uv = 0;
                $ip = 0;
                $crawler = 0;
                $script = 0;
            }
            if ($pv === 0 && $uv === 0 && $ip === 0 && $crawler === 0 && $script === 0 && isset($dbMap[$date])) {
                $pv = $dbMap[$date]['pv'];
                $uv = $dbMap[$date]['uv'];
                $ip = $dbMap[$date]['ip'];
                $crawler = $dbMap[$date]['pv_crawler'];
                $script = $dbMap[$date]['pv_script'];
            }
            $result[] = [
                'date' => $date,
                'pv' => $pv,
                'uv' => $uv,
                'ip' => $ip,
                'pv_crawler' => $crawler,
                'pv_script' => $script,
                'pv_normal' => max(0, $pv - $crawler - $script),
            ];
        }
        return $result;
    }

    /**
     * 最近 N 小时小时级 PV/UV/IP（读 Redis，小时桶 TTL 48h）。
     * 返回最近 N 个完整小时（不含当前不完整小时）。
     *
     * @return list<array{time: string, pv: int, uv: int, ip: int}>
     */
    public function hourly(int $hours = 24): array
    {
        $hours = max(1, min(48, $hours));
        $result = [];
        // 从"上一个小时"往前推 N-1 个小时，跳过当前不完整小时
        for ($i = 1; $i <= $hours; $i++) {
            $ts = strtotime('-' . $i . ' hours');
            if ($ts === false) {
                continue;
            }
            $hKey = date('YmdH', $ts);
            $hTime = date('Y-m-d H:00', $ts);
            try {
                $pv = $this->countOf(VisitKeys::pvHourKey($hKey));
                $uv = $this->redis->pfcount(VisitKeys::uvHourKey($hKey));
                $ip = $this->redis->pfcount(VisitKeys::ipHourKey($hKey));
            } catch (\Throwable) {
                $pv = 0;
                $uv = 0;
                $ip = 0;
            }
            $result[] = ['time' => $hTime, 'pv' => $pv, 'uv' => $uv, 'ip' => $ip];
        }
        // 倒序转正序（时间从旧到新）
        return array_reverse($result);
    }

    /**
     * 读取一个计数 key（缺失返回 0）。
     */
    private function countOf(string $key): int
    {
        $raw = $this->redis->get($key);
        return $raw === null ? 0 : (int)$raw;
    }
}
