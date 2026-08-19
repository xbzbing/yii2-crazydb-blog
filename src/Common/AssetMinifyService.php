<?php

declare(strict_types=1);

namespace App\Common;

use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;

/**
 * 前端资源压缩服务：供 CLI `asset:minify` 与后台 `Cache::rebuild` 共享，
 * 避免 THEMES / minify / fmtBytes 在各调用方重复实现。
 *
 * - CSS/JS 均为"有收益才写回"（压缩后不小于原文则不写）。
 * - 压缩产物直接覆盖主题源文件，由 `asset:minify` 在发布时生成；
 *   与《评审报告》约定一致：仓库保留源文件，产物不入库。
 */
final class AssetMinifyService
{
    /** 参与压缩的主题目录名（相对于 @assets） */
    public const THEMES = ['crazydb', 'main', 'magazine'];

    /**
     * 压缩单个 CSS 文件（有收益才写回）。
     *
     * @param string $file 文件绝对路径
     * @param bool $dryRun 仅预览，不写回文件
     * @return int|null 节省字节数；文件不可读/压缩异常/无收益时返回 null
     */
    public function minifyCss(string $file, bool $dryRun = false): ?int
    {
        $orig = $this->safeSize($file);
        if ($orig === null || $this->isAlreadyMinified($file)) {
            return null;
        }
        try {
            /** @psalm-suppress TooManyArguments MatthiasMullie CSS 构造器接受文件路径（psalm 无法推断可变参数签名） */
            $min = (new CSS($file))->minify();
        } catch (\Throwable) {
            return null;
        }
        return $this->saveIfSmaller($file, $min, $orig, $dryRun);
    }

    /**
     * 压缩单个 JS 文件（有收益才写回）。
     *
     * @param string $file 文件绝对路径
     * @param bool $dryRun 仅预览，不写回文件
     * @return int|null 节省字节数；文件不可读/压缩异常/无收益时返回 null
     */
    public function minifyJs(string $file, bool $dryRun = false): ?int
    {
        $orig = $this->safeSize($file);
        if ($orig === null || $this->isAlreadyMinified($file)) {
            return null;
        }
        try {
            /** @psalm-suppress TooManyArguments MatthiasMullie JS 构造器接受文件路径（psalm 无法推断可变参数签名） */
            $min = (new JS($file))->minify();
        } catch (\Throwable) {
            return null;
        }
        return $this->saveIfSmaller($file, $min, $orig, $dryRun);
    }

    /**
     * 已是压缩产物（*.min.css / *.min.js）的文件跳过再次压缩：
     * 二次 minify 无收益，且会丢弃 vendor 文件的 sourceMappingURL（调试损失）。
     */
    private function isAlreadyMinified(string $file): bool
    {
        return (bool) preg_match('/\.min\.(?:css|js)$/i', $file);
    }

    /**
     * 安全读取文件大小：文件不存在/不可读时返回 null（避免 filesize() 抛 PHP 警告）。
     */
    private function safeSize(string $file): ?int
    {
        if (!is_file($file)) {
            return null;
        }
        $size = @filesize($file);
        return $size === false ? null : $size;
    }

    /**
     * 压缩结果比原文小才回写；否则视为无收益不触碰文件。
     *
     * @return int|null 节省字节数；无收益返回 null
     */
    private function saveIfSmaller(string $file, string $min, int $orig, bool $dryRun): ?int
    {
        $saved = $orig - strlen($min);
        if ($saved <= 0) {
            return null;
        }
        if (!$dryRun) {
            file_put_contents($file, $min);
        }
        return $saved;
    }

    /**
     * 人类可读的字节数（1.2 KB / 340 B）。
     */
    public static function fmtBytes(int $bytes): string
    {
        return $bytes >= 1024 ? sprintf('%.1f KB', $bytes / 1024) : $bytes . ' B';
    }

    /**
     * 递归收集目录下所有 .js 文件（含子目录），返回绝对路径列表。
     *
     * @return list<string>
     */
    public function findJsFiles(string $dir): array
    {
        $files = [];
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS));
        /** @var \SplFileInfo $f */
        foreach ($iter as $f) {
            if ($f->isFile() && $f->getExtension() === 'js') {
                $files[] = $f->getPathname();
            }
        }
        return $files;
    }
}
