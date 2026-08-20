<?php

declare(strict_types=1);

namespace App\Post;

use App\Common\RedisLock;
use Predis\ClientInterface;

/**
 * 文章浏览数 on-demand 惰性同步器（供后台 Dashboard/文章列表触发，搭配 cron 使用）。
 *
 * 降频：距上次同步 < DEBOUNCE 秒则跳过（避免每次页面刷新都跑）。
 * 锁：Redis 互斥，与 post-view/sync 命令/cron 并存安全（防并发重复累加）。
 */
final readonly class PostViewSyncTrigger
{
    /** 降频窗口：距上次成功同步不足此秒数则跳过 */
    public const DEBOUNCE = 60;

    /** 上次同步时间戳 key */
    public const LAST_SYNC_KEY = 'crazydb:post-view:last-sync';

    private const LOCK_KEY = 'crazydb:lock:post-view-sync';

    public function __construct(
        private ClientInterface $redis,
    ) {
    }

    /**
     * 惰性触发：未在降频窗口内则同步一次 pending 文章浏览数据。
     * 返回 true 表示本次确实执行了同步（含 0 条），false 表示被降频/锁跳过。
     */
    public function trigger(): bool
    {
        try {
            $lastRaw = $this->redis->get(self::LAST_SYNC_KEY);
            $last = $lastRaw === null ? 0 : (int)$lastRaw;
            if ($last > 0 && time() - $last < self::DEBOUNCE) {
                return false; // 降频：距上次同步太近，跳过
            }

            $lock = new RedisLock($this->redis);
            if (!$lock->acquire(self::LOCK_KEY, 30)) {
                return false; // 另一实例（cron/并行请求）正在同步
            }
            try {
                // 双重检查：等待锁期间可能已被其他实例更新
                $lastRaw = $this->redis->get(self::LAST_SYNC_KEY);
                $last = $lastRaw === null ? 0 : (int)$lastRaw;
                if ($last > 0 && time() - $last < self::DEBOUNCE) {
                    return false;
                }
                (new PostViewSyncService($this->redis))->syncPending();
                $this->redis->set(self::LAST_SYNC_KEY, (string)time());
            } catch (\Throwable) {
                // 同步失败静默（统计异常不阻断后台页面）
            } finally {
                $lock->release(self::LOCK_KEY);
            }
            return true;
        } catch (\Throwable) {
            // Redis 不可用时静默降级
            return false;
        }
    }
}
