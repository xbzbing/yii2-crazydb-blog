<?php

declare(strict_types=1);

use App\Post\MarkdownRenderer;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Definitions\Reference;

return [
    MarkdownRenderer::class => [
        'class' => MarkdownRenderer::class,
        '__construct()' => [
            'cache' => Reference::to(CacheInterface::class),
        ],
    ],
];
