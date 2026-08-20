<?php

declare(strict_types=1);

use App\Visit\VisitTrackingMiddleware;
use Predis\ClientInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Definitions\Reference;

/** @var array<string, mixed> $params */

return [
    VisitTrackingMiddleware::class => [
        'class' => VisitTrackingMiddleware::class,
        '__construct()' => [
            'redis' => Reference::to(ClientInterface::class),
            'cache' => Reference::to(CacheInterface::class),
            // dbvid cookie 强制 Secure（生产 https 部署：COOKIE_SECURE=1）
            'cookieSecure' => (bool) ($params['cookie']['remember_secure'] ?? false),
        ],
    ],
];
