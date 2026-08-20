<?php

declare(strict_types=1);

namespace App\Post;

use Predis\ClientInterface;

/**
 * 文章浏览数 Redis key 约定（方案 B：无 SCAN、无按日分片）。
 *
 * - PV：累计计数（不按日），每篇文章 1 个 INCR key + 1 个同步游标 key
 * - UV：全量 HLL（不按日，累计去重）
 * - 排行：按日 ZSET（今日/昨日），TTL 48h 自动清理，仅读 Redis，不参与落库
 * - 待同步：pending 集合（Set<postId>），同步时 SMEMBERS 拉取，避免 SCAN
 */
final class PostViewKeys
{
    /** PV 累计计数 key：crazydb:post-view:count:{postId}（INCR，不按日） */
    public const COUNTER_PREFIX = 'crazydb:post-view:count:';

    /** PV 同步游标 key：crazydb:post-view:synced:{postId}（已落库的累计 PV，防重复累加） */
    public const SYNCED_PREFIX = 'crazydb:post-view:synced:';

    /** UV 全量 HLL key：crazydb:post-view:uv:{postId}（PFADD deviceId，不按日） */
    public const UV_PREFIX = 'crazydb:post-view:uv:';

    /** 待同步文章集合：crazydb:post-view:pending（Set<postId>） */
    public const PENDING_KEY = 'crazydb:post-view:pending';

    /** 按日阅读排行 ZSET：crazydb:post-view:top:{Ymd}（ZINCRBY，TTL 48h） */
    public const TOP_PREFIX = 'crazydb:post-view:top:';

    /** 排行 ZSET 有效期：48 小时 */
    public const TOP_TTL = 172800;

    /** 排行返回条数上限 */
    public const TOP_LIMIT = 20;

    public static function counterKey(int $postId): string
    {
        return self::COUNTER_PREFIX . $postId;
    }

    public static function syncedKey(int $postId): string
    {
        return self::SYNCED_PREFIX . $postId;
    }

    public static function uvKey(int $postId): string
    {
        return self::UV_PREFIX . $postId;
    }

    public static function pendingKey(): string
    {
        return self::PENDING_KEY;
    }

    public static function topKey(string $ymd): string
    {
        return self::TOP_PREFIX . $ymd;
    }

    public static function topDateKey(?\DateTimeImmutable $date = null): string
    {
        $date ??= new \DateTimeImmutable();
        return self::topKey($date->format('Ymd'));
    }

    /**
     * 文章删除时清理其全部统计 key（累计计数/游标/UV/排行），无 SCAN。
     * 排行 ZSET 为全站共享 key，只移除该文章成员（ZREM），不 DEL 整个排行。
     * Redis 异常静默（统计残留不影响删除主流程）。
     */
    public static function clearPost(ClientInterface $redis, int $postId): void
    {
        try {
            $now = new \DateTimeImmutable();
            $redis->del([
                self::counterKey($postId),
                self::syncedKey($postId),
                self::uvKey($postId),
            ]);
            // 排行是共享 ZSET：仅移除该文章成员，保留其余排行数据
            $redis->zrem(self::topKey($now->format('Ymd')), (string)$postId);
            $redis->zrem(self::topKey($now->modify('-1 day')->format('Ymd')), (string)$postId);
            $redis->srem(self::pendingKey(), [(string)$postId]);
        } catch (\Throwable) {
            // 统计 key 清理失败不影响文章删除
        }
    }
}
