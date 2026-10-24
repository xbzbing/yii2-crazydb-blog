<?php

declare(strict_types=1);

namespace App\Post\PandocAttributes;

/**
 * 从 `{...}` 块解析出的 Pandoc 属性列表（link_attributes 扩展）。
 *
 * 语法示例（Pandoc MANUAL "Attributes" 一节）：
 *   ![alt](img.jpg){#fig1 .left .big width=300 height=200px style="border:0" alt="新说明"}
 *
 * @see https://raw.githubusercontent.com/jgm/pandoc/main/MANUAL.txt
 */
final class ParsedAttributes
{
    /**
     * @param list<string> $classes `.class` 语法与 `class="a b"` 收集到的类名（已去掉前导点）
     * @param array<string, string> $keyValues `k=v` 形式的键值（键已小写，引号已剥离）
     */
    public function __construct(
        public readonly ?string $id,
        public readonly array $classes,
        public readonly array $keyValues,
    ) {
    }
}
