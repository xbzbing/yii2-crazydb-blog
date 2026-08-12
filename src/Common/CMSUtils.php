<?php

declare(strict_types=1);

namespace App\Common;

use App\Option\Option;
use Yiisoft\Cache\CacheInterface;

final class CMSUtils
{
    private const CACHE_TTL = 3600;

    public static function getSmilies(): array
    {
        return [
            'question', 'razz', 'sad', 'evil', 'exclaim', 'smile', 'redface',
            'biggrin', 'surprised', 'eek', 'confused', 'cool', 'lol', 'mad',
            'twisted', 'rolleyes', 'wink', 'idea', 'arrow', 'neutral', 'cry',
            'mrgreen',
        ];
    }

    /**
     * 获取 Option 配置：缓存（TTL 3600，依赖 option 表 MAX(update_time)）。
     *
     * @return array<string, string|null>
     */
    public static function getSiteConfig(CacheInterface $cache, string $type = 'sys', bool $refresh = false): array
    {
        $cacheKey = 'config_' . $type . '.' . (int)Option::query()->max('update_time');
        if ($refresh) {
            $cache->remove($cacheKey);
        }
        /** @var array<string, string|null> $config */
        $config = $cache->getOrSet(
            $cacheKey,
            static function () use ($type): array {
                $config = [];
                foreach (Option::query()->where(['type' => $type])->all() as $option) {
                    $config[$option->name] = $option->value;
                }
                return $config;
            },
            self::CACHE_TTL,
        );
        return $config;
    }

    /**
     * 获取指定 key 的配置值。
     */
    public static function getSysConfig(CacheInterface $cache, string $key, bool $fresh = false): mixed
    {
        $config = self::getSiteConfig($cache, 'sys', $fresh);
        return $config[$key] ?? null;
    }

    /**
     * 获取 themes 文件夹下的主题列表。
     */
    public static function getThemeList(string $themesBasePath): array
    {
        if (!is_dir($themesBasePath)) {
            return [];
        }
        $themes = [];
        $folder = @opendir($themesBasePath);
        if ($folder === false) {
            return [];
        }
        while (($file = @readdir($folder)) !== false) {
            if ($file[0] !== '.' && is_dir($themesBasePath . DIRECTORY_SEPARATOR . $file)) {
                $themes[$file] = $file;
            }
        }
        closedir($folder);
        ksort($themes);
        return $themes;
    }
}
