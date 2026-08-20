<?php

declare(strict_types=1);

namespace App\Post;

use Predis\ClientInterface;

/**
 * 文章浏览数同步服务（方案 B，无 SCAN）：
 *
 * 读取 pending 集合（SMEMBERS）获取待同步文章，增量落库：
 * - PV：累计计数 vs 同步游标 → delta 累加（防重复）
 * - UV：PFCOUNT 全量覆盖，仅在变化时写
 * - 已删除文章（post 表无记录）：清理孤儿统计 key
 * - 同步完成后从 pending 移除该文章
 *
 * 内部带 Redis 互斥锁（SET NX EX），供定时任务与 on-demand 惰性同步并发安全调用。
 */
final readonly class PostViewSyncService
{
    public function __construct(
        private ClientInterface $redis,
    ) {
    }

    /**
     * 同步一次 pending 列表中所有文章的浏览数据。
     *
     * @return int 本次实际更新的记录数
     */
    public function syncPending(): int
    {
        $postIds = $this->pendingPostIds();
        if ($postIds === []) {
            return 0;
        }
        $synced = 0;
        foreach ($postIds as $postId) {
            $synced += $this->syncPost($postId);
        }
        return $synced;
    }

    /**
     * 同步单篇文章，并从 pending 移除已对齐的该文章。
     */
    private function syncPost(int $postId): int
    {
        // PV 累计计数 vs 游标
        $total = (int)($this->redis->get(PostViewKeys::counterKey($postId)) ?? 0);
        $previous = (int)($this->redis->get(PostViewKeys::syncedKey($postId)) ?? 0);
        $delta = max(0, $total - $previous);

        $post = Post::query()->findByPk($postId);
        if (!$post instanceof Post) {
            // 文章已删除：清理孤儿统计 key（计数/游标/UV/排行）
            PostViewKeys::clearPost($this->redis, $postId);
            return 0;
        }

        $synced = 0;
        if ($delta > 0) {
            $post->updateCounters(['view_count' => $delta]);
            $this->redis->set(PostViewKeys::syncedKey($postId), (string)$total);
            $synced++;
        }

        // UV 全量覆盖（仅变化时写）
        $uvCurrent = $this->redis->pfcount(PostViewKeys::uvKey($postId));
        if ($uvCurrent > 0 && $post->view_uv !== $uvCurrent) {
            $post->view_uv = $uvCurrent;
            $post->save();
            $synced++;
        }

        // 计数与游标对齐后从 pending 移除（消除已完成项，避免下次仍遍历）
        $this->redis->srem(PostViewKeys::pendingKey(), [(string)$postId]);
        return $synced;
    }

    /**
     * @return list<int> pending 集合中的文章 ID（SMEMBERS，无 SCAN）
     */
    private function pendingPostIds(): array
    {
        /** @var list<string> $members */
        $members = $this->redis->smembers(PostViewKeys::pendingKey());
        $ids = [];
        foreach ($members as $m) {
            if (preg_match('/^\d+$/', $m)) {
                $ids[] = (int)$m;
            }
        }
        sort($ids, SORT_NUMERIC);
        return $ids;
    }
}
