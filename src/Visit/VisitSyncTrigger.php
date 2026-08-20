<?php

declare(strict_types=1);

namespace App\Visit;

use App\Common\RedisLock;
use Predis\ClientInterface;

/**
 * 站点日统计 on-demand 惰性同步器（供后台 Dashboard 触发）。
 * 降频 + Redis 锁，与 cron 并发安全（同一把锁：crazydb:lock:visit-sync）。
 */
final readonly class VisitSyncTrigger
{
    /** 降频窗口：距上次成功同步不足此秒数则跳过 */
    public const DEBOUNCE = 300;

    /** 上次同步时间戳 key */
    public const LAST_SYNC_KEY = 'crazydb:visit:last-sync';

    private const LOCK_KEY = 'crazydb:lock:visit-sync';

    public function __construct(
        private ClientInterface $redis,
    ) {
    }

    /**
     * 惰性触发一次站点日统计同步。返回 true 表示本次执行了同步。
     */
    public function trigger(): bool
    {
        try {
            $last = $this->lastSync();
            if ($last > 0 && time() - $last < self::DEBOUNCE) {
                return false;
            }
            $lock = new RedisLock($this->redis);
            if (!$lock->acquire(self::LOCK_KEY, 60)) {
                return false;
            }
            try {
                // 持锁期间可能已被 cron 同步，二次检查（读一次缓存到局部变量，避免重复往返）
                $lastAfterLock = $this->lastSync();
                if ($lastAfterLock > 0 && time() - $lastAfterLock < self::DEBOUNCE) {
                    return false;
                }
                (new VisitSyncService($this->redis))->syncPending();
                $this->redis->set(self::LAST_SYNC_KEY, (string)time());
            } catch (\Throwable) {
                // 统计同步失败静默（不阻断后台页面）
            } finally {
                $lock->release(self::LOCK_KEY);
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function lastSync(): int
    {
        $raw = $this->redis->get(self::LAST_SYNC_KEY);
        return $raw === null ? 0 : (int)$raw;
    }
}
