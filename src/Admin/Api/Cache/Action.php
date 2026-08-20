<?php

declare(strict_types=1);

namespace App\Admin\Api\Cache;

use App\Admin\Api\JsonResponse;
use App\Common\AssetMinifyService;
use App\Common\CacheKeyIndex;
use App\Common\CacheKeys;
use Predis\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Aliases\Aliases;

/**
 * 后台缓存管理 JSON API：
 * - GET  /admin/api/cache           缓存状态（Redis 版本/内存/缓存 key 数/命中）
 * - POST /admin/api/cache/clear     清理应用缓存
 * - POST /admin/api/cache/rebuild   更新前端资源（压缩 CSS/JS + touch 目录触发 hash 重算）
 */
final readonly class Action
{
    public function __construct(
        private JsonResponse $jsonResponse,
        private ClientInterface $redis,
        private Aliases $aliases,
        private AssetMinifyService $assetMinify,
        private CacheKeyIndex $cacheKeyIndex,
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
            'totalKeys' => $this->cacheKeyIndex->indexedCount(),
            'hits' => (int)($stats['keyspace_hits'] ?? 0),
            'misses' => (int)($stats['keyspace_misses'] ?? 0),
            'connectedClients' => (int)($raw['connected_clients'] ?? 0),
            'db' => (string)CacheKeys::REDIS_DB,
        ]);
    }

    public function clear(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // 无 SCAN：通过缓存 key 索引集批量删除
            $keys = $this->cacheKeyIndex->indexedKeys();
            $ok = $this->cacheKeyIndex->clear();
            if (!$ok) {
                // 索引不可用：不误删同 DB 其他数据，返回失败让管理员介入
                return $this->jsonResponse->fail('缓存清理失败：Redis 索引不可用，未执行删除（避免误删统计等数据）。');
            }
            $deleted = count($keys);
        } catch (\Throwable $e) {
            return $this->jsonResponse->fail('缓存清理失败：' . $e->getMessage());
        }
        return $this->jsonResponse->ok(['message' => '缓存已清空。', 'deletedKeys' => $deleted]);
    }

    /**
     * 重新构建前端资源：压缩主题 CSS/JS + touch 目录触发 AssetPublisher hash 重算。
     */
    public function rebuild(ServerRequestInterface $request): ResponseInterface
    {
        $aliases = $this->aliases->get('@assets');
        $saved = 0;
        $files = 0;

        foreach (AssetMinifyService::THEMES as $theme) {
            // 压缩根目录 CSS
            $rootCss = "{$aliases}/{$theme}/site.css";
            if (is_file($rootCss)) {
                $rs = $this->assetMinify->minifyCss($rootCss);
                if ($rs !== null) {
                    $saved += $rs;
                    $files++;
                }
            }
            // 压缩 css/ 子目录
            $cssDir = "{$aliases}/{$theme}/css";
            if (is_dir($cssDir)) {
                $cssFiles = glob("{$cssDir}/*.css");
                if (is_array($cssFiles)) {
                    foreach ($cssFiles as $file) {
                        $rs = $this->assetMinify->minifyCss($file);
                        if ($rs !== null) {
                            $saved += $rs;
                            $files++;
                        }
                    }
                }
            }
            // 触发 hash 重算
            if (is_dir("{$aliases}/{$theme}")) {
                touch("{$aliases}/{$theme}");
            }
            if (is_dir($cssDir)) {
                touch($cssDir);
            }
        }

        return $this->jsonResponse->ok([
            'message' => '资源已更新。压缩 ' . $files . ' 个文件，节省 ' . AssetMinifyService::fmtBytes($saved) . '。',
            'files' => $files,
            'saved' => $saved,
        ]);
    }

}
