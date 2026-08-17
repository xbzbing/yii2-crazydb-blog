<?php

declare(strict_types=1);

namespace App\Visit;

use Predis\ClientInterface;

/**
 * 访问统计服务：读 Redis 实时 PV/UV，组合 MySQL 历史数据成趋势序列。
 */
final readonly class VisitService
{
    public function __construct(
        private ClientInterface $redis,
    ) {
    }

    /**
     * 今日实时 PV/UV（读 Redis，未同步也实时可见）。
     *
     * @return array{pv: int, uv: int}
     */
    public function today(): array
    {
        $ymd = date('Ymd');
        try {
            $pvRaw = $this->redis->get(VisitKeys::pvKey($ymd));
            $pv = $pvRaw === null ? 0 : (int)$pvRaw;
            $uv = $this->redis->pfcount(VisitKeys::uvKey($ymd));
        } catch (\Throwable) {
            $pv = 0;
            $uv = 0;
        }
        return ['pv' => $pv, 'uv' => $uv];
    }

    /**
     * 最近 N 天每日 PV/UV 趋势（Redis 实时数据优先，历史日期从 MySQL 补）。
     *
     * @return list<array{date: string, pv: int, uv: int}>
     */
    public function trend(int $days): array
    {
        $days = max(1, min(90, $days));
        $fromTs = strtotime('-' . ($days - 1) . ' days');
        $from = $fromTs === false ? date('Y-m-d') : date('Y-m-d', $fromTs);
        $to = date('Y-m-d');

        // 历史：MySQL（已同步的数据）
        $fromDb = VisitDaily::range($from, $to);
        $dbMap = [];
        foreach ($fromDb as $row) {
            $dbMap[$row['date']] = $row;
        }

        // 实时：Redis 覆盖最近 N 天（可能含未同步的增量）
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $ts = strtotime('-' . $i . ' days');
            $date = $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
            $ymd = str_replace('-', '', $date);
            try {
                $pvRaw = $this->redis->get(VisitKeys::pvKey($ymd));
                $pv = $pvRaw === null ? 0 : (int)$pvRaw;
                $uv = $this->redis->pfcount(VisitKeys::uvKey($ymd));
            } catch (\Throwable) {
                $pv = 0;
                $uv = 0;
            }
            if ($pv === 0 && $uv === 0 && isset($dbMap[$date])) {
                $pv = $dbMap[$date]['pv'];
                $uv = $dbMap[$date]['uv'];
            }
            $result[] = ['date' => $date, 'pv' => $pv, 'uv' => $uv];
        }
        return $result;
    }
}
