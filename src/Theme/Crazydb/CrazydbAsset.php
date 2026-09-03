<?php

declare(strict_types=1);

namespace App\Theme\Crazydb;

use Yiisoft\Assets\AssetBundle;

/**
 * Crazydb 主题资源：Bootstrap 5.3 + FontAwesome 6 + 主题样式（提取自 Yii2 线上模板）。
 * 全部 vendored，无 CDN 依赖。
 */
final class CrazydbAsset extends AssetBundle
{
    public ?string $basePath = '@assets/crazydb';
    public ?string $baseUrl = '@assetsUrl/crazydb';
    public ?string $sourcePath = '@assetsSource/crazydb';

    /** @var array<array-key, mixed> */
    public array $css = [
        'vendor/fontawesome/css/all.min.css',
        'vendor/bootstrap/css/bootstrap.min.css',
        'css/site.css',
    ];

    /** @var array<array-key, mixed> */
    public array $js = [
        'vendor/bootstrap/js/bootstrap.bundle.min.js',
    ];
}
