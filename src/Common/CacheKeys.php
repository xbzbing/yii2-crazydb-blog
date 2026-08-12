<?php

declare(strict_types=1);

namespace App\Common;

/**
 * 缓存 key 约定。
 *
 * 所有应用缓存 key 统一带前缀，便于后台「清空缓存」时按前缀精准清理
 * （SCAN + DEL），避免误删 Redis 内其他数据（如 visit:* 访问统计）。
 */
final class CacheKeys
{
    /** 应用缓存 key 统一前缀（与 config/common/di/cache.php 的 PrefixedCache prefix 一致）。
     *  注意：不能含 RedisCache 校验黑名单字符 {}()/\@:（见 yiisoft/cache-redis validateKey） */
    public const PREFIX = 'crazydbcache_';

    /** SCAN 匹配缓存 key 的模式 */
    public const PATTERN = self::PREFIX . '*';
}
