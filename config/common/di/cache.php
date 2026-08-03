<?php

declare(strict_types=1);

use Predis\Client;
use Predis\ClientInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Redis\RedisCache;
use Yiisoft\Definitions\Reference;

return [
    ClientInterface::class => [
        'class' => Client::class,
        '__construct()' => [
            'parameters' => sprintf(
                'tcp://%s:%s%s',
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

    CacheInterface::class => [
        'class' => Cache::class,
        '__construct()' => [
            'handler' => Reference::to(RedisCache::class),
        ],
    ],
];
