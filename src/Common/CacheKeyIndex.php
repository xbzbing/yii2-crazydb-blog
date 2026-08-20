<?php

declare(strict_types=1);

namespace App\Common;

use Predis\ClientInterface;
use Psr\SimpleCache\CacheInterface as PsrSimpleCacheInterface;

/**
 * 缓存 key 索引装饰器：维护"当前存在的缓存 key"的 Redis Set，用于无 SCAN 的清缓存。
 *
 * 背景：生产 Redis 可能禁用 SCAN（或以高成本实现）。清缓存若按前缀 SCAN+DEL 不可行时，
 * 改为登记索引：每次缓存写（set/setMultiple）把真实 key 加入 Set，删除时 SREM，
 * 清缓存时 SMEMBERS + DEL + DEL 索引。全程无 SCAN。
 *
 * 注意：缓存 key 带 TTL 自然过期时不会 SREM（索引会有残留），但清缓存时 DEL 不存在 key 是
 * 空操作，且清完后 DEL 整个索引 Set，内存回到基线——可接受。
 */
final readonly class CacheKeyIndex implements PsrSimpleCacheInterface
{
    /** 缓存 key 索引 Set 的 Redis key（应用前缀下） */
    public const INDEX_KEY = CacheKeys::PREFIX . 'index';

    public function __construct(
        private PsrSimpleCacheInterface $inner,
        private ClientInterface $redis,
        private string $prefix,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->inner->get($key, $default);
    }

    public function set(string $key, mixed $value, \DateInterval|null|int $ttl = null): bool
    {
        $this->redis->sadd(self::INDEX_KEY, [$this->prefixed($key)]);
        return $this->inner->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        $this->redis->srem(self::INDEX_KEY, [$this->prefixed($key)]);
        return $this->inner->delete($key);
    }

    public function has(string $key): bool
    {
        return $this->inner->has($key);
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->inner->getMultiple($keys, $default);
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    public function setMultiple(iterable $values, \DateInterval|null|int $ttl = null): bool
    {
        $prefixed = [];
        foreach (array_keys($values instanceof \Traversable ? iterator_to_array($values) : $values) as $k) {
            $prefixed[] = $this->prefixed((string)$k); // @psalm-suppress RedundantCastGivenDocblockType
        }
        if ($prefixed !== []) {
            $this->redis->sadd(self::INDEX_KEY, $prefixed);
        }
        return $this->inner->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $prefixed = [];
        foreach ($keys as $k) {
            // PSR-16 文档约定 $keys 元素为 string，直接拼接
            $prefixed[] = $this->prefixed($k);
        }
        if ($prefixed !== []) {
            $this->redis->srem(self::INDEX_KEY, $prefixed);
        }
        return $this->inner->deleteMultiple($keys);
    }

    public function clear(): bool
    {
        // 用索引集批量删除（无 SCAN），随后清空索引。
        // 索引不可用时**不**退化为 inner->clear()（RedisCache 会 FLUSHDB，波及同 DB 的统计等其他数据），
        // 而是返回 false 让调用方（Cache/Action）向管理员报错，避免误删。
        try {
            /** @var list<string> $members */
            $members = $this->redis->smembers(self::INDEX_KEY);
            if ($members !== []) {
                $this->redis->del($members);
            }
            $this->redis->del([self::INDEX_KEY]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 当前索引中的缓存 key 数（可能含已过期残留，仅作展示）。
     */
    public function indexedCount(): int
    {
        try {
            return $this->redis->scard(self::INDEX_KEY);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * 索引中的全部真实 key（供清缓存时 DEL）。
     *
     * @return list<string>
     */
    public function indexedKeys(): array
    {
        try {
            /** @var list<string> $members */
            $members = $this->redis->smembers(self::INDEX_KEY);
            return $members;
        } catch (\Throwable) {
            return [];
        }
    }

    private function prefixed(mixed $key): string
    {
        // inner（PrefixedCache）最终存的是 $this->prefix . $key
        return $this->prefix . (string)$key;
    }
}
