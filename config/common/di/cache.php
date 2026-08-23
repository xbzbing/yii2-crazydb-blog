<?php

declare(strict_types=1);

use Predis\Client;
use Predis\ClientInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\PrefixedCache;
use Yiisoft\Cache\Redis\RedisCache;
use Yiisoft\Definitions\Reference;
use App\Common\CacheKeys;

return [
    ClientInterface::class => [
        'class' => Client::class,
        '__construct()' => [
            'parameters' => sprintf(
                // 指定 DB 与 Visit 统计隔离（避免多应用共享 Redis 时 key 碰撞/误删）
                'tcp://%s:%s/%d%s',
                (string) ($_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST')) ?: '127.0.0.1',
                (string) ($_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT')) ?: '6379',
                CacheKeys::REDIS_DB,
                (string) ($_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD')) !== ''
                    ? '?password=' . rawurlencode((string) ($_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD')))
                    : '',
            ),
        ],
    ],

    RedisCache::class => [
        'class' => RedisCache::class,
    ],

    // 缓存 key 统一前缀：清理缓存 key 时按索引集精准删除（无 SCAN），
    // 不会误伤同 Redis 内的其他数据（如 visit:* 访问统计等）。
    'cache.prefixed' => [
        'class' => PrefixedCache::class,
        '__construct()' => [
            'cache' => Reference::to(RedisCache::class),
            'prefix' => CacheKeys::PREFIX,
        ],
    ],

    // 缓存 key 索引装饰器：为清缓存维护 key 集合（写时 SADD，等同 SCAN 的枚举能力但无 SCAN）。
    // 主 ID 用类名（消费方按具体类 type-hint，如 Admin\Api\Cache\Action）；别名供 Reference 引用。
    \App\Common\CacheKeyIndex::class => [
        'class' => \App\Common\CacheKeyIndex::class,
        '__construct()' => [
            'inner' => Reference::to('cache.prefixed'),
            'redis' => Reference::to(ClientInterface::class),
            'prefix' => CacheKeys::PREFIX,
        ],
    ],

    'cache.key-index' => Reference::to(\App\Common\CacheKeyIndex::class),

    CacheInterface::class => [
        'class' => Cache::class,
        '__construct()' => [
            'handler' => Reference::to('cache.key-index'),
        ],
    ],
];
