<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Admin;

use Yiisoft\Assets\AssetBundle;

/**
 * 后台管理样式（admin-header/sidebar/main 布局 + 表格/表单/统计卡）。
 */
final class AdminAsset extends AssetBundle
{
    public ?string $basePath = '@assets/admin';
    public ?string $baseUrl = '@assetsUrl/admin';
    public ?string $sourcePath = '@assetsSource/admin';

    public array $css = [
        'css/admin.css',
    ];
}
