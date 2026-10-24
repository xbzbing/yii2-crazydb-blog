<?php

declare(strict_types=1);

namespace App\Web\Shared;

use App\Common\CMSUtils;
use App\Option\Option;
use Yiisoft\Cache\CacheInterface;

/**
 * SEO 配置（option 表 seo 组）的懒加载只读视图，供 layout 等无 Action 上下文的地方使用。
 * 数据由 CMSUtils 缓存（TTL 1h + update_time 版本键），首次访问才查库。
 */
final class SeoConfig
{
    /** @var array<string, string|null>|null */
    private ?array $values = null;

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        return $this->values ??= CMSUtils::getSiteConfig($this->cache, Option::SEO_TYPE);
    }

    public function get(string $name): string
    {
        return (string)($this->all()[$name] ?? '');
    }

    /**
     * 站点绝对 URL（归一化后，未配置返回空串）。
     */
    public function siteUrl(): string
    {
        return SeoUrl::site($this->get(Option::SEO_SITE_URL));
    }
}
