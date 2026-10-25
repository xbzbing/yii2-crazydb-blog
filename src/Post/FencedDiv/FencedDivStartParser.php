<?php

declare(strict_types=1);

namespace App\Post\FencedDiv;

use App\Post\PandocAttributes\PandocAttributesParser;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

/**
 * 围栏 div 的开栏识别（Pandoc "Extension: fenced_divs"）。
 *
 * 匹配整行：`{0-3 空格}{3+ 个冒号}{空白}{…属性块}{行尾}`。
 *
 * 与 Pandoc 的刻意差异（对齐本站 PandocAttributesParser 的收敛原则）：
 * 1. **开栏必须带 `{…}` 属性块**：裸 `:::` 只作闭栏（无匹配开栏时按字面量输出）。
 *    否则一处笔误的裸 `:::` 会把文章剩余内容全部包进未闭合 div（HTMLPurifier 兜底
 *    闭合），代价远大于收益；`::: {结构非法}`（如 `{note: hello}`）同样整行字面量。
 * 2. 属性块必须与开栏同行（Pandoc 允许换行写属性块，不支持）。
 * 3. 属性 token 复用 PandocAttributesParser（`#id` `.class` `k=v` 与白名单冒号键），
 *    未知键消费后忽略，不透传任意 HTML 属性（由 HTMLPurifier 白名单兜底）。
 *
 * 闭栏在 FencedDivParser::tryContinue 中识别（容器 continue 优先于新的 block start），
 * 因此这里的裸 `:::` 只可能是「div 之外的游离行」→ 字面量。
 */
final class FencedDivStartParser implements BlockStartParserInterface
{
    /** 开栏整行：缩进(≤3，4+ 已由 isIndented 拦截) + 3+ 冒号 + 单行属性块 */
    private const OPEN = '/\G[ \t]*(:{3,})[ \t]*(\{.*\})[ \t]*$/';

    /** 从 matchInPlace 返回的整行里取组（\G 锚点仅用于行内定位，这里对整行重匹配） */
    private const OPEN_GROUPS = '/^[ \t]*(:{3,})[ \t]*(\{.*\})[ \t]*$/';

    public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
    {
        if ($cursor->isIndented()) {
            return BlockStart::none();
        }

        $state = $cursor->saveState();
        $line = $cursor->matchInPlace(self::OPEN);
        if ($line === null) {
            $cursor->restoreState($state);

            return BlockStart::none();
        }

        $groups = [];
        if (\preg_match(self::OPEN_GROUPS, $line, $groups) !== 1) {
            // 防御分支：OPEN 能匹配则必然能取组；失败时回滚为字面量
            $cursor->restoreState($state);

            return BlockStart::none();
        }

        $attributes = PandocAttributesParser::parseAttributeBlock($groups[2]);
        if ($attributes === null) {
            // 属性块整体结构非法（须与行内场景同一套 BLOCK 校验，防局部 token 吞文本）：整行保持字面量
            $cursor->restoreState($state);

            return BlockStart::none();
        }

        return BlockStart::of(new FencedDivParser(\strlen($groups[1]), $attributes))->at($cursor);
    }
}
