<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * 站点维护页（首页在维护模式下渲染）。
 *
 * @var Yiisoft\View\WebView $this
 * @var App\Shared\ApplicationParams $applicationParams
 * @var array<string, string|null> $siteConfig
 * @var string $maintenanceMessage
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$siteName = (string)($siteConfig['site_name'] ?? $applicationParams->name);
$message = $maintenanceMessage !== '' ? $maintenanceMessage : '系统升级中';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>维护中</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            font: 16px/1.8 -apple-system, BlinkMacSystemFont, "PingFang SC", "Hiragino Sans GB", "Noto Sans SC", "Microsoft YaHei", sans-serif;
            background: linear-gradient(160deg, #f6f4f0 0%, #eae6df 100%);
            color: #2b2823;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance {
            max-width: 520px;
            width: 90%;
            padding: 56px 40px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(43, 40, 35, .12);
            text-align: center;
        }
        .maintenance-icon {
            width: 84px;
            height: 84px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: #f3ede4;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance-icon svg { width: 44px; height: 44px; }
        .maintenance h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .maintenance-message {
            font-size: 17px;
            color: #6b675f;
            margin-bottom: 8px;
        }
        .maintenance-note {
            font-size: 13px;
            color: #9a948a;
            margin-top: 24px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            display: inline-block;
            width: 18px; height: 18px;
            border: 3px solid #d8d2c8;
            border-top-color: #b4432c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            vertical-align: -3px;
            margin-right: 6px;
        }
    </style>
</head>
<body>
<div class="maintenance">
    <div class="maintenance-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="#b4432c" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3.2"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
        </svg>
    </div>
    <h1>维护中</h1>
    <p class="maintenance-message"><?= Html::encode($message) ?></p>
    <p class="maintenance-note">
        <span class="spinner"></span><?= Html::encode($siteName) ?> 正在维护，请稍后再来访问
    </p>
</div>
</body>
</html>