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
        $stats = $this->dayStats(date('Ymd'));
        return [
            'pv' => $stats['pv'],
            'uv' => $stats['uv'],
            'ip' => $stats['ip'],
            'pv_crawler' => $stats['pv_crawler'],
            'pv_script' => $stats['pv_script'],
            'pv_normal' => max(0, $stats['pv'] - $stats['pv_crawler'] - $stats['pv_script']),
        ];
    }

    /**
     * 昨日 PV/UV/IP（Redis 优先，缺失回退 MySQL visit_daily）——供仪表盘涨跌对比。
     *
     * @return array{pv: int, uv: int, ip: int}
     */
    public function yesterday(): array
    {
        $ts = strtotime('-1 days');
        $ymd = $ts === false ? date('Ymd') : date('Ymd', $ts);
        $stats = $this->dayStats($ymd);

        // Redis 无该日数据（key 已被 sync 清理）→ 回退 DB
        if ($stats['pv'] === 0 && $stats['uv'] === 0 && $stats['ip'] === 0) {
            $date = substr($ymd, 0, 4) . '-' . substr($ymd, 4, 2) . '-' . substr($ymd, 6, 2);
            foreach (VisitDaily::range($date, $date) as $row) {
                return ['pv' => $row['pv'], 'uv' => $row['uv'], 'ip' => $row['ip']];
            }
        }
        return ['pv' => $stats['pv'], 'uv' => $stats['uv'], 'ip' => $stats['ip']];
    }

    /**
     * 指定日期的 Redis 实时统计（内部共用）。
     *
     * @return array{pv: int, uv: int, ip: int, pv_crawler: int, pv_script: int}
     */
    private function dayStats(string $ymd): array
    {
        try {
            /** @var \Predis\Client $client */
            $client = $this->redis;
            /** @var list<mixed> $results pipeline 按命令顺序返回 [pv, uv, ip, crawler, script] */
            $results = $client->pipeline(
            /** @param \Predis\Pipeline\Pipeline $pipe Predis Pipeline 命令走 __call（psalm 无法识别，已逐行抑制） */
            static function ($pipe) use ($ymd): void {
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->get(VisitKeys::pvKey($ymd));
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->pfcount(VisitKeys::uvKey($ymd));
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->pfcount(VisitKeys::ipKey($ymd));
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->get(VisitKeys::crawlerKey($ymd));
                /** @psalm-suppress UndefinedMagicMethod */
                $pipe->get(VisitKeys::scriptKey($ymd));
            },
            );
            $int = static fn (mixed $v): int => $v === null ? 0 : (int)$v;
            return [
                'pv' => $int($results[0] ?? null),
                'uv' => $int($results[1] ?? null),
                'ip' => $int($results[2] ?? null),
                'pv_crawler' => $int($results[3] ?? null),
                'pv_script' => $int($results[4] ?? null),
            ];
        } catch (\Throwable) {
            return ['pv' => 0, 'uv' => 0, 'ip' => 0, 'pv_crawler' => 0, 'pv_script' => 0];
        }
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
