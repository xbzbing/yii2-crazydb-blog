<?php

declare(strict_types=1);

namespace App\Post\FencedDiv;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * Pandoc `fenced_divs` 围栏容器节点：
 *
 *     ::: {.center}
 *     ![示意图](test.png){width=70%}
 *     :::
 *
 * 渲染为 <div class="center">…</div>；属性在解析期写入 data['attributes']，
 * 由 FencedDivRenderer 读取输出（HTMLPurifier 最终裁定可保留的属性）。
 *
 * @see https://raw.githubusercontent.com/jgm/pandoc/main/MANUAL.txt "Extension: fenced_divs"
 */
final class FencedDiv extends AbstractBlock {}
