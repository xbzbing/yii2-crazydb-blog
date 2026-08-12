<?php

declare(strict_types=1);

use App\Captcha\CaptchaService;
use Yiisoft\Definitions\Reference;
use Yiisoft\Session\SessionInterface;

$captchaDebugRaw = (string) ($_ENV['CAPTCHA_DEBUG'] ?? getenv('CAPTCHA_DEBUG'));
$captchaDebug = $captchaDebugRaw !== '' && filter_var($captchaDebugRaw, FILTER_VALIDATE_BOOLEAN);

return [
    CaptchaService::class => [
        'class' => CaptchaService::class,
        '__construct()' => [
            'session' => Reference::to(SessionInterface::class),
            'debug' => $captchaDebug,
        ],
    ],
];
