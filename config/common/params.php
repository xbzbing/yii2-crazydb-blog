<?php

declare(strict_types=1);

use App\Shared\ApplicationParams;
use App\User\AuthService;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;

$mailerDebugRaw = (string) ($_ENV['MAILER_DEBUG'] ?? getenv('MAILER_DEBUG'));
$mailerDebug = $mailerDebugRaw !== ''
    ? filter_var($mailerDebugRaw, FILTER_VALIDATE_BOOLEAN)
    : (($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'dev');

return [
    'application' => require __DIR__ . '/application.php',

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    'yiisoft/view' => [
        'basePath' => null,
        'theme' => [
            'pathMap' => [
                '@src/Web' => '@root/themes/magazine',
            ],
        ],
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'applicationParams' => Reference::to(ApplicationParams::class),
            'aliases' => Reference::to(Aliases::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
            'flash' => Reference::to(FlashInterface::class),
            'authService' => Reference::to(AuthService::class),
        ],
    ],

    'yiisoft/yii-view-renderer' => [
        'viewPath' => '@src/Web/Shared',
        'layout' => '@src/Web/Shared/Layout/Main/layout.php',
        'injections' => [
            Reference::to(CsrfViewInjection::class),
        ],
    ],

    'yiisoft/db-mysql' => [
        'dsn' => sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            (string) ($_ENV['DB_HOST'] ?? getenv('DB_HOST')) ?: '127.0.0.1',
            (string) ($_ENV['DB_PORT'] ?? getenv('DB_PORT')) ?: '3306',
            (string) ($_ENV['DB_NAME'] ?? getenv('DB_NAME')) ?: 'crazydb',
        ),
        'username' => (string) ($_ENV['DB_USER'] ?? getenv('DB_USER')) ?: 'root',
        'password' => (string) ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD')),
    ],

    'mailer' => [
        // SMTP DSN（symfony/mailer 格式），未配置时为 null:// 不发送；可用环境变量 MAILER_DSN 覆盖
        'dsn' => (string) ($_ENV['MAILER_DSN'] ?? getenv('MAILER_DSN')) ?: 'null://null',
        'admin_email' => 'root@crazydb.com',
        'notice_email' => 'notice@crazydb.com',
        // debug 模式下所有邮件只发往 admin_email（对齐 Yii2 YII_DEBUG 语义）
        'debug' => $mailerDebug,
    ],
];
