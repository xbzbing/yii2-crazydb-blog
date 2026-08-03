<?php

declare(strict_types=1);

use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Session\Session;
use Yiisoft\Session\SessionInterface;

/** @var array $params */

/**
 * Session/Flash DI 定义：统一到同一 Session 实例（Session::class 与
 * SessionInterface 若各自解析会得到两个实例，session cookie 无法跨中间件发出）。
 * 镜像 yiisoft/session 包 di-web.php 定义（common 优先覆盖），
 * 必须显式传 options（cookie_secure=0）——否则 session_start 用 php.ini 默认（secure=1 会在 http 下报错）。
 */
return [
    SessionInterface::class => [
        'class' => Session::class,
        '__construct()' => [
            $params['yiisoft/session']['session']['options'],
            $params['yiisoft/session']['session']['handler'],
        ],
        'reset' => function (): void {
            $this->sessionId = null;
            $this->close();
        },
    ],
    FlashInterface::class => Flash::class,
];
