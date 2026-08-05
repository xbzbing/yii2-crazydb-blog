<?php

declare(strict_types=1);

namespace App\Admin\Api\Cache;

use App\Admin\Api\JsonResponse;
use App\Common\CacheKeys;
use Predis\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;

/**
 * 后台缓存管理 JSON API：
 * - GET  /admin/api/cache           缓存状态（Redis 版本/内存/缓存 key 数/命中）
 * - POST /admin/api/cache/clear     仅清理应用缓存（按 crazydbcache_* 前缀精准删除，
 *                                    不会 flushdb 误删 Redis 内其他数据）
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private CacheInterface $cache,
        private ClientInterface $redis,
    ) {
    }

    public function status(ServerRequestInterface $request): ResponseInterface
    {
        $info = [];
        try {
            $info = (array)$this->redis->info();
            $raw = isset($info['Server']) && is_array($info['Server']) ? $info['Server'] : $info;
        } catch (\Throwable $e) {
            return $this->jsonResponse->ok([
                'connected' => false,
                'error' => $e->getMessage(),
            ]);
        }

        $memory = isset($info['Memory']) && is_array($info['Memory']) ? $info['Memory'] : $info;
        $stats = isset($info['Stats']) && is_array($info['Stats']) ? $info['Stats'] : $info;

        return $this->jsonResponse->ok([
            'connected' => true,
            'redisVersion' => (string)($raw['redis_version'] ?? ''),
            'uptime' => (int)($raw['uptime_in_seconds'] ?? 0),
            'usedMemory' => (int)($memory['used_memory'] ?? 0),
            'usedMemoryPeak' => (int)($memory['used_memory_peak'] ?? 0),
            'maxMemory' => (int)($memory['maxmemory'] ?? 0),
            'totalKeys' => $this->countPrefixedKeys(),
            'hits' => (int)($stats['keyspace_hits'] ?? 0),
            'misses' => (int)($stats['keyspace_misses'] ?? 0),
            'connectedClients' => (int)($raw['connected_clients'] ?? 0),
            'db' => (string)CacheKeys::REDIS_DB,
        ]);
    }

    public function clear(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $deleted = $this->deleteByPrefix(CacheKeys::PREFIX);
        } catch (\Throwable $e) {
            return $this->jsonResponse->fail('缓存清理失败：' . $e->getMessage());
        }
        return $this->jsonResponse->ok(['message' => '缓存已清空。', 'deletedKeys' => $deleted]);
    }

    /**
     * 统计应用缓存 key 数量（仅缓存前缀，不包含同 Redis 内其他数据）。
     */
    private function countPrefixedKeys(): int
    {
        $count = 0;
        $cursor = 0;
        do {
            /** @var array{0: string, 1: list<string>} $result */
            $result = $this->redis->scan($cursor, ['match' => CacheKeys::PATTERN, 'count' => 500]);
            $cursor = (int)$result[0];
            foreach ($result[1] as $key) {
                if (str_starts_with((string)$key, CacheKeys::PREFIX)) {
                    $count++;
                }
            }
        } while ($cursor !== 0);
        return $count;
    }

    /**
     * SCAN 按前缀批量删除缓存 key，返回删除数量。
     */
    private function deleteByPrefix(string $prefix): int
    {
        $cursor = 0;
        $total = 0;
        do {
            /** @var array{0: string, 1: list<string>} $result */
            $result = $this->redis->scan($cursor, ['match' => $prefix . '*', 'count' => 500]);
            $cursor = (int)$result[0];
            $keys = $result[1];
            if ($keys !== []) {
                $total += $this->redis->del($keys);
            }
        } while ($cursor !== 0);
        return $total;
    }
}
