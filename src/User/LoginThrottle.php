<?php

declare(strict_types=1);

namespace App\User;

use Throwable;
use Yiisoft\Cache\CacheInterface;

/**
 * 登录失败限流：按归一化用户名与客户端 IP 的组合记录失败次数。
 *
 * 缓存不可用时降级为不限制，避免 Redis 故障阻断正常登录；调用方仍应记录
 * 基础认证日志以便运营侧发现基础设施故障。
 */
final readonly class LoginThrottle
{
    public const MAX_FAILURES = 5;
    public const LOCK_SECONDS = 900;
    private const FAILURE_WINDOW_SECONDS = 900;

    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * 记录一次失败；达到阈值后返回锁定剩余秒数，否则返回 0。
     */
    public function recordFailure(string $username, string $clientIp, string $scope = 'login'): int
    {
        try {
            $psr = $this->cache->psr();
            $failureKey = $this->key($scope, 'fail', $username, $clientIp);
            $failures = (int) $psr->get($failureKey, 0) + 1;
            if ($failures < self::MAX_FAILURES) {
                $psr->set($failureKey, $failures, self::FAILURE_WINDOW_SECONDS);
                return 0;
            }

            $psr->set($this->key($scope, 'lock', $username, $clientIp), time() + self::LOCK_SECONDS, self::LOCK_SECONDS);
            $psr->delete($failureKey);
            return self::LOCK_SECONDS;
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * 获取当前锁定的剩余秒数；缓存不可用或不存在时返回 0。
     */
    public function remaining(string $username, string $clientIp, string $scope = 'login'): int
    {
        try {
            $psr = $this->cache->psr();
            $key = $this->key($scope, 'lock', $username, $clientIp);
            $until = (int) $psr->get($key, 0);
            $remaining = $until - time();
            if ($remaining <= 0 && $until !== 0) {
                $psr->delete($key);
            }
            return max(0, $remaining);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * 登录成功后清除该账号和客户端 IP 的失败状态。
     */
    public function clear(string $username, string $clientIp, string $scope = 'login'): void
    {
        try {
            $psr = $this->cache->psr();
            $psr->delete($this->key($scope, 'fail', $username, $clientIp));
            $psr->delete($this->key($scope, 'lock', $username, $clientIp));
        } catch (Throwable) {
        }
    }

    private function key(string $scope, string $type, string $username, string $clientIp): string
    {
        $identity = mb_strtolower(trim($username)) . "\0" . trim($clientIp);
        return 'credential_throttle_' . hash('sha256', $scope) . '_' . $type . '_' . hash('sha256', $identity);
    }
}
