<?php

declare(strict_types=1);

namespace App\Post;

/**
 * 文章浏览数 Redis key 约定：
 * - PV：按日分片（便于同步后清理历史计数）
 * - UV：全量 HLL（不按日，累计去重）
 */
final class PostViewKeys
{
    /** PV 计数 key：crazydb:post-view:count:{Ymd}:{postId}（INCR） */
    public const COUNTER_PREFIX = 'crazydb:post-view:count:';

    /** PV 同步游标 key：crazydb:post-view:synced:{Ymd}:{postId} */
    public const SYNCED_PREFIX = 'crazydb:post-view:synced:';

    /** UV 全量 HLL key：crazydb:post-view:uv:{postId}（PFADD deviceId，不按日） */
    public const UV_PREFIX = 'crazydb:post-view:uv:';

    public static function counterKey(int $postId, ?string $ymd = null): string
    {
        return self::COUNTER_PREFIX . ($ymd ?? date('Ymd')) . ':' . $postId;
    }

    public static function syncedKey(int $postId, ?string $ymd = null): string
    {
        return self::SYNCED_PREFIX . ($ymd ?? date('Ymd')) . ':' . $postId;
    }

    public static function uvKey(int $postId): string
    {
        return self::UV_PREFIX . $postId;
    }
}
