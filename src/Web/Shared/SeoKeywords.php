<?php

declare(strict_types=1);

namespace App\Web\Shared;

/**
 * SEO keywords 合并工具：页面级关键词（文章标签 / 分类关键词 / 标签名）在前，
 * 站点级 seo_keywords 追加在后，去空白 + 忽略大小写去重。
 */
final class SeoKeywords
{
    /**
     * 合并多段关键词（每段内部以英文逗号分隔）。
     *
     * 前面的段优先：重复关键词只保留第一次出现的位置。
     */
    public static function merge(string ...$segments): string
    {
        $keywords = [];
        $seen = [];
        foreach ($segments as $segment) {
            foreach (explode(',', $segment) as $keyword) {
                $keyword = trim($keyword);
                if ($keyword === '') {
                    continue;
                }
                $key = mb_strtolower($keyword, 'UTF-8');
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $keywords[] = $keyword;
            }
        }
        return implode(',', $keywords);
    }
}
