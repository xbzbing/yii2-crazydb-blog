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
    /** PV 计数 key：crazydb:visit:pv:{Ymd}（INCR） */
    public const PV_PREFIX = 'crazydb:visit:pv:';

    /** UV HyperLogLog key：crazydb:visit:uv:{Ymd}（PFADD/PFCOUNT） */
    public const UV_PREFIX = 'crazydb:visit:uv:';

    /** PV 已同步游标 key：crazydb:visit:synced:{Ymd}（记录已落库的 PV 数，防重复累加） */
    public const SYNCED_PREFIX = 'crazydb:visit:synced:';

    public static function pvKey(string $ymd): string
    {
        return self::PV_PREFIX . $ymd;
    }

    public static function uvKey(string $ymd): string
    {
        return self::UV_PREFIX . $ymd;
    }

    public static function syncedKey(string $ymd): string
    {
        return self::SYNCED_PREFIX . $ymd;
    }
}
