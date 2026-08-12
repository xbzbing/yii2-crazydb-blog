<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Admin;

use Yiisoft\Assets\AssetBundle;

/**
 * 后台管理资源：AdminLTE 4（Bootstrap 5）+ FontAwesome 6 + 本地细节样式。
 * 全部 vendored（assets/admin/vendor），无 CDN 依赖。
 */
final class AdminAsset extends AssetBundle
{
    public ?string $basePath = '@assets/admin';
    public ?string $baseUrl = '@assetsUrl/admin';
    public ?string $sourcePath = '@assetsSource/admin';

    public array $css = [
        'vendor/fontawesome/css/all.min.css',
        'vendor/bootstrap/css/bootstrap.min.css',
        'vendor/adminlte/css/adminlte.min.css',
        'css/admin.css',
    ];

    public array $js = [
        'vendor/bootstrap/js/bootstrap.bundle.min.js',
        'vendor/adminlte/js/adminlte.min.js',
    ];
}
