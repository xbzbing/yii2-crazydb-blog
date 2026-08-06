<?php

declare(strict_types=1);

namespace App\Post;

/**
 * 文章浏览数 Redis key 约定：按日分片，便于同步后清理历史计数。
 */
final class PostViewKeys
{
    public const COUNTER_PREFIX = 'crazydb:post-view:count:';
    public const SYNCED_PREFIX = 'crazydb:post-view:synced:';

    public static function counterKey(int $postId, ?string $ymd = null): string
    {
        return self::COUNTER_PREFIX . ($ymd ?? date('Ymd')) . ':' . $postId;
    }

    public static function syncedKey(int $postId, ?string $ymd = null): string
    {
        return self::SYNCED_PREFIX . ($ymd ?? date('Ymd')) . ':' . $postId;
    }
}
