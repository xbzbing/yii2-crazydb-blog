<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Common\CacheKeyIndex;
use App\Common\CacheKeys;
use App\Tests\TestCase;
use Yiisoft\Cache\ArrayCache;

final class CacheKeyIndexTest extends TestCase
{
    private InMemoryRedisStub $redis;

    private CacheKeyIndex $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = new InMemoryRedisStub();
        $this->cache = new CacheKeyIndex(new ArrayCache(), $this->redis, CacheKeys::PREFIX);
    }

    public function testSetIndexesKeyAndGetReturnsValue(): void
    {
        $this->assertTrue($this->cache->set('foo', 'bar'));

        $this->assertSame('bar', $this->cache->get('foo'));
        // 索引集已登记真实 key（含前缀）
        $this->assertSame(['crazydbcache_foo'], $this->redis->smembers(CacheKeyIndex::INDEX_KEY));
    }

    public function testDeleteRemovesFromIndexAndInner(): void
    {
        $this->cache->set('foo', 'bar');

        $this->assertTrue($this->cache->delete('foo'));

        $this->assertNull($this->cache->get('foo'));
        $this->assertSame([], $this->redis->smembers(CacheKeyIndex::INDEX_KEY));
    }

    public function testClearDeletesIndexedKeysAndEmptiesIndex(): void
    {
        $this->cache->set('a', '1');
        $this->cache->set('b', '2');

        $this->assertTrue($this->cache->clear());

        // 索引集已清空
        $this->assertSame([], $this->redis->smembers(CacheKeyIndex::INDEX_KEY));
        // 全部索引 key 已 DEL（生产 inner 为 RedisCache，删真实 key 即清 inner；测试用 ArrayCache 无法直查）
        $delCalls = array_values(array_filter(
            $this->redis->calls,
            static fn (array $c): bool => $c[0] === 'del',
        ));
        $deletedKeys = array_merge(...array_map(static fn (array $c): array => $c[1][0], $delCalls));
        sort($deletedKeys);
        // 索引 key 本身也被 DEL（clear 语义：连索引一起清空）
        $this->assertSame(['crazydbcache_a', 'crazydbcache_b', CacheKeyIndex::INDEX_KEY], $deletedKeys);
    }

    public function testIndexedCountAndKeys(): void
    {
        $this->cache->set('a', '1');
        $this->cache->set('b', '2');

        $this->assertSame(2, $this->cache->indexedCount());
        $keys = $this->cache->indexedKeys();
        sort($keys);
        $this->assertSame(['crazydbcache_a', 'crazydbcache_b'], $keys);
    }

    public function testStaleIndexEntriesAreTolerated(): void
    {
        // 索引残留（key 已过期但未 SREM，如 TTL 自然过期）：清缓存时 DEL 空操作即可
        $this->redis->sadd(CacheKeyIndex::INDEX_KEY, ['crazydbcache_expired']);

        $this->assertTrue($this->cache->clear());

        $this->assertSame([], $this->redis->smembers(CacheKeyIndex::INDEX_KEY));
    }

    public function testSetMultipleIndexesAllKeys(): void
    {
        $this->assertTrue($this->cache->setMultiple(['x' => '1', 'y' => '2']));

        $this->assertSame(['1', '2'], array_values(iterator_to_array($this->cache->getMultiple(['x', 'y']))));
        $this->assertSame(2, $this->cache->indexedCount());
    }
}