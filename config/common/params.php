<?php

declare(strict_types=1);

use App\Post\MarkdownRenderer;
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

// 记住我 cookie Secure 标记：https 部署时设 COOKIE_SECURE=1（默认 0 兼容本地 http）
$cookieSecureRaw = (string) ($_ENV['COOKIE_SECURE'] ?? getenv('COOKIE_SECURE'));
$cookieSecure = $cookieSecureRaw !== '' && filter_var($cookieSecureRaw, FILTER_VALIDATE_BOOLEAN);

$tablePrefix = (string) ($_ENV['DB_TABLE_PREFIX'] ?? getenv('DB_TABLE_PREFIX'));
$tablePrefix = $tablePrefix !== '' ? $tablePrefix : 'blog_';

return [
    'application' => require __DIR__ . '/application.php',

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    'yiisoft/view' => [
        'basePath' => null,
        // 弃用：主题由后台配置（option 表 theme，见 ThemeFactory::AVAILABLE_THEMES）
        // 动态切换；此处静态 pathMap 若启用会绕过后台配置，保持空 map 勿填。
        'theme' => [
            'pathMap' => [],
        ],
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'applicationParams' => Reference::to(ApplicationParams::class),
            'aliases' => Reference::to(Aliases::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
            'flash' => Reference::to(FlashInterface::class),
            'authService' => Reference::to(AuthService::class),
            'markdownRenderer' => Reference::to(MarkdownRenderer::class),
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
        // 表前缀（Yii2 遗留 blog_），模型 tableName() 用 {{%xxx}} 占位
        'table_prefix' => $tablePrefix,
    ],

    'mailer' => [
        // SMTP DSN（symfony/mailer 格式），未配置时为 null:// 不发送；可用环境变量 MAILER_DSN 覆盖
        'dsn' => (string) ($_ENV['MAILER_DSN'] ?? getenv('MAILER_DSN')) ?: 'null://null',
        'admin_email' => 'root@crazydb.com',
        'notice_email' => 'notice@crazydb.com',
        // debug 模式下所有邮件只发往 admin_email（对齐 Yii2 YII_DEBUG 语义）
        'debug' => $mailerDebug,
    ],

    'cookie' => [
        'remember_secure' => $cookieSecure,
    ],
];
