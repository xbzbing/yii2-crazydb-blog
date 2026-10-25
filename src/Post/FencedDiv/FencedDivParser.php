<?php

declare(strict_types=1);

namespace App\Post\FencedDiv;

use App\Post\PandocAttributes\ParsedAttributes;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Util\RegexHelper;

/**
 * 围栏 div 的容器续行解析：
 *
 * - 闭栏：行首（缩进 ≤3）起 `3+ 冒号` 且其后仅空白、冒号数不少于开栏 → 整行消费并闭合
 *   （对齐 FencedCodeParser 的闭栅栏判定；带属性的 `::: {.x}` 不是闭栏，会继续往下
 *   由 FencedDivStartParser 开出嵌套 div）。
 * - 其余行：不消费任何前缀（div 内容与文档同级解析，缩进 4+ 仍是普通缩进代码块），
 *   由 block start 循环处理（嵌套开栏/普通段落）。
 * - 空行不分隔闭合：div 跨空行包裹内容（与 Pandoc 一致）。
 *
 * 未闭合的 div 延伸到文档结束（同未闭合围栏代码块），HTMLPurifier 兜底闭合标签。
 */
final class FencedDivParser extends AbstractBlockContinueParser
{
    private readonly FencedDiv $block;

    private readonly int $fenceLength;

    public function __construct(int $fenceLength, ParsedAttributes $attributes)
    {
        $this->block       = new FencedDiv();
        $this->fenceLength = $fenceLength;
        $this->block->data->set('attributes', self::toHtmlAttributes($attributes));
    }

    public function getBlock(): FencedDiv
    {
        return $this->block;
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        // 与 BlockQuote 同策略：任意块级内容（含嵌套 div）均可容纳
        return true;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): BlockContinue
    {
        if (! $cursor->isIndented() && $cursor->getNextNonSpaceCharacter() === ':') {
            $match = RegexHelper::matchFirst('/^:{3,}(?=[ \t]*$)/', $cursor->getLine(), $cursor->getNextNonSpacePosition());
            if ($match !== null && \strlen($match[0]) >= $this->fenceLength && ! $this->hasNestedDiv($activeBlockParser->getBlock())) {
                // 闭栏归最内层：容器 continue 按外→内顺序匹配，等长的嵌套 `:::` 若由外层
                // 先收口会把内层连同其内容一起弹出（已实测复现），因此 active 链上存在
                // 更深的 div 时本层让行，交给内层 tryContinue 以 finished() 收口。
                return BlockContinue::finished();
            }
        }

        return BlockContinue::at($cursor);
    }

    /**
     * active 块到本 div 之间是否还隔着另一层 div（隔着则闭栏属于更内层）。
     *
     * 形参按 ?Node 收窄：psalm 对 `AbstractBlock` 上继承来的 `parent()` 推断为 mixed
     * （Node 上声明则正常，实测复现），而 AbstractBlock 本就是 Node 子类，无碍运行。
     */
    private function hasNestedDiv(?Node $node): bool
    {
        while ($node !== null && $node !== $this->block) {
            if ($node instanceof FencedDiv) {
                return true;
            }
            $parent = $node->parent();
            if ($parent === null) {
                return false;
            }
            $node = $parent;
        }

        return false;
    }

    /**
     * 属性 → HTML 属性数组（只输出对 div 有意义的白名单键）。
     *
     * 与 Pandoc「任意属性透传为 HTML 属性」的差异：width/height/alt 对 div 无意义，
     * 未知键（data-* 等）透传会被 HTMLPurifier 剥掉，统一消费后忽略
     * （对齐 PandocAttributesParser 类注释差异 2）。
     *
     * @return array<string, string>
     */
    private static function toHtmlAttributes(ParsedAttributes $attributes): array
    {
        $attrs  = [];
        $style  = [];
        $classes = $attributes->classes;

        foreach ($attributes->keyValues as $key => $value) {
            if ($value === '') {
                continue;
            }
            switch ($key) {
                case 'style':
                    $style[] = $value;
                    break;
                case 'class':
                    foreach (\array_filter(\explode(' ', $value)) as $class) {
                        $classes[] = $class;
                    }
                    break;
                case 'id':
                    $attrs['id'] = $value;
                    break;
                case 'title':
                    $attrs['title'] = $value;
                    break;
                default:
                    // width/height/alt/未知键：div 上无意义，忽略
                    break;
            }
        }

        if ($attributes->id !== null && $attributes->id !== '') {
            $attrs['id'] = $attributes->id;
        }
        if ($classes !== []) {
            $attrs['class'] = \implode(' ', \array_unique($classes));
        }
        if ($style !== []) {
            $attrs['style'] = \implode(';', $style);
        }

        return $attrs;
    }
}
