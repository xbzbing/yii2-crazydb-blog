<?php

declare(strict_types=1);

namespace App\Common;

use Predis\ClientInterface;

/**
 * Redis 互斥锁（SET NX EX）：用于并发安全地执行互斥任务（如统计同步）。
 * 持锁超过 TTL 自动释放，防止任务卡死。
 */
final readonly class RedisLock
{
    public function __construct(
        private ClientInterface $redis,
    ) {
    }

    public const DEFAULT_TTL = 30;

    /**
     * 尝试获取锁。成功返回 true，已持有/获取失败返回 false。
     */
    public function acquire(string $key, int $ttl = self::DEFAULT_TTL): bool
    {
        try {
            // Predis: set(key, value, EX, ttl, NX) → 成功返回 Status('OK')，NX 未获得返回 null
            $result = $this->redis->set($key, (string)time(), 'EX', $ttl, 'NX');
            return $result !== null;
        } catch (\Throwable) {
            // Redis 不可用时放行（统计同步失败静默，不阻塞业务）
            return true;
        }
    }

    public function release(string $key): void
    {
        try {
            $this->redis->del([$key]);
        } catch (\Throwable) {
        }
    }
}
