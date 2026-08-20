<?php

declare(strict_types=1);

namespace App\Visit;

/**
 * 访问统计 Redis key 约定。
 *
 * 前缀 crazydb:visit: 与缓存（crazydbcache_*）隔离——后台「清空缓存」按缓存前缀
 * 精准删除，不会误伤统计数据。
 * 命名遵循 Redis 惯例（冒号层级分隔）：crazydb:visit:pv:20260805。
 */
final class VisitKeys
{
    // ── 日维度 ────────────────────────────────────────────────────────────

    /** PV 计数 key：crazydb:visit:pv:{Ymd}（INCR） */
    public const PV_PREFIX = 'crazydb:visit:pv:';

    /** UV HyperLogLog key：crazydb:visit:uv:{Ymd}（PFADD deviceId，仅正常访问） */
    public const UV_PREFIX = 'crazydb:visit:uv:';

    /** IP HyperLogLog key：crazydb:visit:ip:{Ymd}（PFADD IP，全部访问） */
    public const IP_PREFIX = 'crazydb:visit:ip:';

    /** 爬虫访问 PV key：crazydb:visit:pv_crawler:{Ymd}（INCR） */
    public const CRAWLER_PREFIX = 'crazydb:visit:pv_crawler:';

    /** 脚本访问 PV key：crazydb:visit:pv_script:{Ymd}（INCR） */
    public const SCRIPT_PREFIX = 'crazydb:visit:pv_script:';

    // ── 小时维度 ──────────────────────────────────────────────────────────

    /** 小时 PV：crazydb:visit:pv1h:{YmdH}（INCR，全部访问） */
    public const PV_HOUR_PREFIX = 'crazydb:visit:pv1h:';

    /** 小时 UV：crazydb:visit:uv1h:{YmdH}（HLL deviceId，仅正常访问） */
    public const UV_HOUR_PREFIX = 'crazydb:visit:uv1h:';

    /** 小时 IP：crazydb:visit:ip1h:{YmdH}（HLL IP，全部访问） */
    public const IP_HOUR_PREFIX = 'crazydb:visit:ip1h:';

    /** 小时 key 有效期：48 小时（覆盖 24h 图 + 清理缓冲） */
    public const HOUR_TTL = 172800;

    // ── 同步游标 ──────────────────────────────────────────────────────────

    public const SYNCED_PREFIX = 'crazydb:visit:synced:';
    public const CRAWLER_SYNCED_PREFIX = 'crazydb:visit:synced_crawler:';
    public const SCRIPT_SYNCED_PREFIX = 'crazydb:visit:synced_script:';

    /** 有统计数据的日期索引集合：crazydb:visit:dates（Set<Ymd>，写入侧 SADD，供无 SCAN 枚举+清理） */
    public const DATES_KEY = 'crazydb:visit:dates';

    // ── 日维度方法 ────────────────────────────────────────────────────────

    public static function pvKey(string $ymd): string { return self::PV_PREFIX . $ymd; }
    public static function uvKey(string $ymd): string { return self::UV_PREFIX . $ymd; }
    public static function ipKey(string $ymd): string { return self::IP_PREFIX . $ymd; }
    public static function crawlerKey(string $ymd): string { return self::CRAWLER_PREFIX . $ymd; }
    public static function scriptKey(string $ymd): string { return self::SCRIPT_PREFIX . $ymd; }

    public static function syncedKey(string $ymd): string { return self::SYNCED_PREFIX . $ymd; }
    public static function crawlerSyncedKey(string $ymd): string { return self::CRAWLER_SYNCED_PREFIX . $ymd; }
    public static function scriptSyncedKey(string $ymd): string { return self::SCRIPT_SYNCED_PREFIX . $ymd; }
    public static function datesKey(): string { return self::DATES_KEY; }

    // ── 小时维度方法 ──────────────────────────────────────────────────────

    public static function pvHourKey(string $ymdH): string { return self::PV_HOUR_PREFIX . $ymdH; }
    public static function uvHourKey(string $ymdH): string { return self::UV_HOUR_PREFIX . $ymdH; }
    public static function ipHourKey(string $ymdH): string { return self::IP_HOUR_PREFIX . $ymdH; }
}
