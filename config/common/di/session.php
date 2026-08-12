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
 * 必须显式传 options（cookie_secure 默认 0）——否则 session_start 用 php.ini 默认
 * （secure=1 会在 http 下报错）。HTTPS 生产部署设 COOKIE_SECURE=1 后，
 * session cookie 同步启用 Secure（与记住我 cookie 共用同一开关，见 params.php）。
 */
$sessionOptions = $params['yiisoft/session']['session']['options'] ?? [];
$sessionOptions['cookie_secure'] = (bool) ($params['cookie']['remember_secure'] ?? false) ? 1 : 0;

return [
    SessionInterface::class => [
        'class' => Session::class,
        '__construct()' => [
            $sessionOptions,
            $params['yiisoft/session']['session']['handler'],
        ],
        'reset' => function (): void {
            $this->sessionId = null;
            $this->close();
        },
    ],
    FlashInterface::class => Flash::class,
];
