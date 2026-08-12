<?php

declare(strict_types=1);

namespace App\Theme\Magazine;

use Yiisoft\Assets\AssetBundle;

final class MagazineAsset extends AssetBundle
{
    public ?string $basePath = '@assets/magazine';
    public ?string $baseUrl = '@assetsUrl/magazine';
    public ?string $sourcePath = '@assetsSource/magazine';

    public array $css = [
        'magazine.css',
    ];
}
