<?php

declare(strict_types=1);

use App\User\AuthService;
use App\User\RegisterService;
use App\User\RememberMeMiddleware;
use App\User\SessionAuthMethod;
use App\User\UserRepository;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;
use Yiisoft\Definitions\Reference;

/** @var array $params */

return [
    UserRepository::class => [
        'class' => UserRepository::class,
    ],
    IdentityRepositoryInterface::class => UserRepository::class,
    IdentityWithTokenRepositoryInterface::class => UserRepository::class,
    SessionAuthMethod::class => [
        'class' => SessionAuthMethod::class,
    ],
    AuthService::class => [
        'class' => AuthService::class,
    ],
    RegisterService::class => [
        'class' => RegisterService::class,
    ],
    RememberMeMiddleware::class => [
        'class' => RememberMeMiddleware::class,
        '__construct()' => [
            'cookieSecure' => (bool) ($params['cookie']['remember_secure'] ?? false),
        ],
    ],
    \App\Web\Login\Action::class => [
        'class' => \App\Web\Login\Action::class,
        '__construct()' => [
            'rememberCookieSecure' => (bool) ($params['cookie']['remember_secure'] ?? false),
        ],
    ],
    \App\Web\Logout\Action::class => [
        'class' => \App\Web\Logout\Action::class,
        '__construct()' => [
            'rememberCookieSecure' => (bool) ($params['cookie']['remember_secure'] ?? false),
        ],
    ],
];
