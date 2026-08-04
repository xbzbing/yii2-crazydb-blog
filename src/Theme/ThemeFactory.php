<?php

declare(strict_types=1);

namespace App\Theme;

use App\Common\CMSUtils;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\View\Theme;

/**
 * 动态主题工厂：从 option 表读取前台主题配置（type=sys, name=theme），
 * 构建 Theme pathMap（aliases 解析为绝对路径——applyTo 用 str_starts_with 匹配）。
 * 白名单校验防止路径注入；空值 = 默认主题（无覆盖）。
 *
 * 当前可用主题（白名单）：
 * - ''       默认主题（src/Web 视图原样渲染）
 * - magazine 墨刊主题（themes/magazine）
 *
 * 新增主题时：创建 themes/{name} 目录并加入 AVAILABLE_THEMES。
 */
final class ThemeFactory
{
    /** @var array<string, string> 可用主题：配置值 => 主题目录名 */
    public const AVAILABLE_THEMES = [
        '' => '',
        'magazine' => 'magazine',
    ];

    /**
     * @param array<string, string> $themeParams 静态配置（vendor 默认 pathMap 等，暂不合并）
     */
    public static function create(ContainerInterface $container): Theme
    {
        $pathMap = [];
        try {
            /** @var CacheInterface $cache */
            $cache = $container->get(CacheInterface::class);
            $config = CMSUtils::getSiteConfig($cache);
            $theme = $config['theme'] ?? '';
        } catch (\Throwable $e) {
            // 缓存/DB 异常时降级为默认主题，但记录日志便于排查"主题未切换"。
            if ($container->has(LoggerInterface::class)) {
                /** @var LoggerInterface $logger */
                $logger = $container->get(LoggerInterface::class);
                $logger->warning('ThemeFactory: 读取 theme 配置失败，降级为默认主题', ['error' => $e->getMessage()]);
            }
            $theme = '';
        }

        if ($theme !== '' && isset(self::AVAILABLE_THEMES[$theme])) {
            /** @var Aliases $aliases */
            $aliases = $container->get(Aliases::class);
            $pathMap[$aliases->get('@src/Web')] = [$aliases->get('@root/themes/' . self::AVAILABLE_THEMES[$theme])];
        }

        return new Theme($pathMap, '', '');
    }
}
