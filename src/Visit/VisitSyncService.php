<?php

declare(strict_types=1);

namespace App\Visit;

use Predis\ClientInterface;

/**
 * 站点日统计同步服务（无 SCAN）：
 *
 * 枚举最近 KEEP_REDIS_DAYS 天（固定窗口），把 Redis PV/UV/IP/分类增量落库到 visit_daily。
 * 不含锁——由命令（visit/sync）与 on-demand 触发器各自持锁调用。
 */
final readonly class VisitSyncService
{
    public const KEEP_REDIS_DAYS = 30;

    public function __construct(
        private ClientInterface $redis,
    ) {
    }

    /**
     * 同步最近窗口内所有有数据的日期，返回实际更新的天数。
     */
    public function syncPending(): int
    {
        $synced = 0;
        $today = date('Ymd');
        foreach ($this->dates() as $ymd) {
            $pvTotal = $this->countOf(VisitKeys::pvKey($ymd));
            if ($pvTotal === 0) {
                continue; // 该日无数据
            }
            $uvTotal = $this->redis->pfcount(VisitKeys::uvKey($ymd));
            $ipTotal = $this->redis->pfcount(VisitKeys::ipKey($ymd));
            $crawlerTotal = $this->countOf(VisitKeys::crawlerKey($ymd));
            $scriptTotal = $this->countOf(VisitKeys::scriptKey($ymd));

            $deltaPv = max(0, $pvTotal - $this->countOf(VisitKeys::syncedKey($ymd)));
            $deltaCrawler = max(0, $crawlerTotal - $this->countOf(VisitKeys::crawlerSyncedKey($ymd)));
            $deltaScript = max(0, $scriptTotal - $this->countOf(VisitKeys::scriptSyncedKey($ymd)));

            if ($deltaPv > 0 || $uvTotal > 0 || $ipTotal > 0 || $deltaCrawler > 0 || $deltaScript > 0) {
                VisitDaily::upsertByDate(
                    $this->ymdToDate($ymd),
                    $deltaPv,
                    $uvTotal,
                    $ipTotal,
                    $deltaCrawler,
                    $deltaScript,
                );
                $this->redis->set(VisitKeys::syncedKey($ymd), (string)$pvTotal);
                $this->redis->set(VisitKeys::crawlerSyncedKey($ymd), (string)$crawlerTotal);
                $this->redis->set(VisitKeys::scriptSyncedKey($ymd), (string)$scriptTotal);
                $synced++;
            }

            // 非今天且超过保留期：清理 Redis（含无 TTL 的 synced 游标 key）
            if ($ymd < $today && $this->isOlderThanKeep($ymd)) {
                $this->redis->del([
                    VisitKeys::pvKey($ymd),
                    VisitKeys::uvKey($ymd),
                    VisitKeys::ipKey($ymd),
                    VisitKeys::crawlerKey($ymd),
                    VisitKeys::scriptKey($ymd),
                    VisitKeys::syncedKey($ymd),
                    VisitKeys::crawlerSyncedKey($ymd),
                    VisitKeys::scriptSyncedKey($ymd),
                ]);
                // 从日期索引集中移除，避免下次再枚举
                $this->redis->srem(VisitKeys::datesKey(), [$ymd]);
            }
        }
        return $synced;
    }

    private function countOf(string $key): int
    {
        $raw = $this->redis->get($key);
        return $raw === null ? 0 : (int)$raw;
    }

    /**
     * 待处理日期列表（无 SCAN）：写入侧索引集（visit:dates）+ 固定窗口并集，
     * 兼容"索引集缺失（旧数据）"与"今日刚写入尚未入索引"两种情况。
     *
     * @return list<string> YYYYMMDD，升序
     */
    private function dates(): array
    {
        $set = [];
        try {
            /** @var list<string> $members */
            $members = $this->redis->smembers(VisitKeys::datesKey());
            foreach ($members as $m) {
                if (preg_match('/^\d{8}$/', $m)) {
                    $set[] = $m;
                }
            }
        } catch (\Throwable) {
        }
        // 固定窗口（含今天）：即使索引集异常/未登记也能覆盖最近 N 天
        for ($i = 0; $i < self::KEEP_REDIS_DAYS; $i++) {
            $ts = strtotime('-' . $i . ' days');
            if ($ts !== false) {
                $ymd = date('Ymd', $ts);
                if (!in_array($ymd, $set, true)) {
                    $set[] = $ymd;
                }
            }
        }
        sort($set);
        return $set;
    }

    private function ymdToDate(string $ymd): string
    {
        return substr($ymd, 0, 4) . '-' . substr($ymd, 4, 2) . '-' . substr($ymd, 6, 2);
    }

    private function isOlderThanKeep(string $ymd): bool
    {
        $keepFromTs = strtotime('-' . self::KEEP_REDIS_DAYS . ' days');
        if ($keepFromTs === false) {
            return false;
        }
        return $ymd < date('Ymd', $keepFromTs);
    }
}
