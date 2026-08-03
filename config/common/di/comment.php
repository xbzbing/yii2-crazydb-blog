<?php

declare(strict_types=1);

use App\Comment\CommentService;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    CommentService::class => [
        'class' => CommentService::class,
        '__construct()' => [
            'cache' => Reference::to(\Yiisoft\Cache\CacheInterface::class),
            'captcha' => Reference::to(\App\Captcha\CaptchaService::class),
            'noticeService' => Reference::to(\App\Mail\NoticeService::class),
            'adminEmail' => (string) $params['mailer']['admin_email'],
        ],
    ],
];
