<?php

declare(strict_types=1);

namespace App\Post;

use Predis\ClientInterface;

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

    /**
     * 文章删除时清理其全部统计 key：UV 全量 HLL + 按日分片的 PV 计数/游标。
     * PV 计数与游标按日分片，无法直接拼 key，需按 `*:{postId}` 模式 SCAN 后删除。
     * Redis 异常静默（统计残留不影响删除主流程）。
     */
    public static function clearPost(ClientInterface $redis, int $postId): void
    {
        try {
            $keys = [self::uvKey($postId)];
            $cursor = 0;
            foreach ([self::COUNTER_PREFIX, self::SYNCED_PREFIX] as $prefix) {
                do {
                    /** @var array{0: string, 1: list<string>} $result */
                    $result = $redis->scan($cursor, ['match' => $prefix . '*:' . $postId, 'count' => 500]);
                    $cursor = (int)$result[0];
                    foreach ($result[1] as $key) {
                        $keys[] = $key;
                    }
                } while ($cursor !== 0);
            }
            $redis->del(array_values(array_unique($keys)));
        } catch (\Throwable) {
            // 统计 key 清理失败不影响文章删除
        }
    }
}
