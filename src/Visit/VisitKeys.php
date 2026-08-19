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

    /** 爬虫访问 PV key：crazydb:visit:pv_crawler:{Ymd}（INCR） */
    public const CRAWLER_PREFIX = 'crazydb:visit:pv_crawler:';

    /** 脚本访问 PV key：crazydb:visit:pv_script:{Ymd}（INCR） */
    public const SCRIPT_PREFIX = 'crazydb:visit:pv_script:';

    /** PV 已同步游标 key：crazydb:visit:synced:{Ymd}（记录已落库的 PV 数，防重复累加） */
    public const SYNCED_PREFIX = 'crazydb:visit:synced:';

    /** 爬虫 PV 已同步游标 key：crazydb:visit:synced_crawler:{Ymd} */
    public const CRAWLER_SYNCED_PREFIX = 'crazydb:visit:synced_crawler:';

    /** 脚本 PV 已同步游标 key：crazydb:visit:synced_script:{Ymd} */
    public const SCRIPT_SYNCED_PREFIX = 'crazydb:visit:synced_script:';

    public static function pvKey(string $ymd): string
    {
        return self::PV_PREFIX . $ymd;
    }

    public static function uvKey(string $ymd): string
    {
        return self::UV_PREFIX . $ymd;
    }

    public static function crawlerKey(string $ymd): string
    {
        return self::CRAWLER_PREFIX . $ymd;
    }

    public static function scriptKey(string $ymd): string
    {
        return self::SCRIPT_PREFIX . $ymd;
    }

    public static function syncedKey(string $ymd): string
    {
        return self::SYNCED_PREFIX . $ymd;
    }

    public static function crawlerSyncedKey(string $ymd): string
    {
        return self::CRAWLER_SYNCED_PREFIX . $ymd;
    }

    public static function scriptSyncedKey(string $ymd): string
    {
        return self::SCRIPT_SYNCED_PREFIX . $ymd;
    }
}
