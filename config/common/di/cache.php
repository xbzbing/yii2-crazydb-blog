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
                // 指定 DB 1 与应用隔离（避免多应用共享 Redis 时 key 碰撞/误删）
                'tcp://%s:%s/1%s',
                (string) ($_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST')) ?: '127.0.0.1',
                (string) ($_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT')) ?: '6379',
                (string) ($_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD')) !== ''
                    ? '?password=' . (string) ($_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD'))
                    : '',
            ),
        ],
    ],

    RedisCache::class => [
        'class' => RedisCache::class,
    ],

    // 缓存 key 统一前缀：清理缓存时按前缀精准删除（SCAN），
    // 不会误伤同 Redis 内的其他数据（如 visit:* 访问统计等）。
    'cache.prefixed' => [
        'class' => PrefixedCache::class,
        '__construct()' => [
            'cache' => Reference::to(RedisCache::class),
            'prefix' => CacheKeys::PREFIX,
        ],
    ],

    CacheInterface::class => [
        'class' => Cache::class,
        '__construct()' => [
            'handler' => Reference::to('cache.prefixed'),
        ],
    ],
];
