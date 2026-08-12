<?php

declare(strict_types=1);

use App\Mail\NoticeService;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Mailer\Symfony\Mailer;

/** @var array $params */

return [
    TransportInterface::class => DynamicReference::to(
        static fn() => Transport::fromDsn((string) $params['mailer']['dsn']),
    ),

    MailerInterface::class => [
        'class' => Mailer::class,
        '__construct()' => [
            'transport' => Reference::to(TransportInterface::class),
        ],
    ],

    NoticeService::class => [
        'class' => NoticeService::class,
        '__construct()' => [
            'mailer' => Reference::to(MailerInterface::class),
            'adminEmail' => (string) $params['mailer']['admin_email'],
            'noticeEmail' => (string) $params['mailer']['notice_email'],
            'debug' => (bool) $params['mailer']['debug'],
        ],
    ],
];
