<?php

declare(strict_types=1);

use App\User\AuthService;
use App\User\RegisterService;
use App\User\SessionAuthMethod;
use App\User\UserRepository;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Auth\IdentityWithTokenRepositoryInterface;

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
];
