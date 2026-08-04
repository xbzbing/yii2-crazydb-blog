<?php

declare(strict_types=1);

namespace App\Admin\Api\Cache;

use App\Admin\Api\JsonResponse;
use Predis\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Cache\CacheInterface;

/**
 * 后台缓存管理 JSON API：
 * - GET  /admin/api/cache           缓存状态（Redis 版本/内存/keys/命中）
 * - POST /admin/api/cache/clear     清空全部应用缓存（Redis DB1）
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
        $keyspace = isset($info['Keyspace']) && is_array($info['Keyspace']) ? $info['Keyspace'] : [];

        $db1 = $keyspace['db1'] ?? null;
        $keys = 0;
        if (is_string($db1) && preg_match('/keys=(\d+)/', $db1, $m)) {
            $keys = (int)$m[1];
        }

        return $this->jsonResponse->ok([
            'connected' => true,
            'redisVersion' => (string)($raw['redis_version'] ?? ''),
            'uptime' => (int)($raw['uptime_in_seconds'] ?? 0),
            'usedMemory' => (int)($memory['used_memory'] ?? 0),
            'usedMemoryPeak' => (int)($memory['used_memory_peak'] ?? 0),
            'maxMemory' => (int)($memory['maxmemory'] ?? 0),
            'totalKeys' => $keys,
            'hits' => (int)($stats['keyspace_hits'] ?? 0),
            'misses' => (int)($stats['keyspace_misses'] ?? 0),
            'connectedClients' => (int)($raw['connected_clients'] ?? 0),
            'db' => '1',
        ]);
    }

    public function clear(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->cache->psr()->clear();
        } catch (\Throwable $e) {
            return $this->jsonResponse->fail('缓存清理失败：' . $e->getMessage());
        }
        return $this->jsonResponse->ok(['message' => '缓存已清空。']);
    }
}
