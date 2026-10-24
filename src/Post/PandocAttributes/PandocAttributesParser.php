<?php

declare(strict_types=1);

namespace App\Post\PandocAttributes;

use League\CommonMark\Extension\CommonMark\Node\Inline\AbstractWebResource;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Pandoc `link_attributes` 扩展语法的 inline parser：
 *
 *     ![alt](img.jpg){#fig1 .left width=300 height=200px style="border:0" alt="新说明"}
 *
 * 语义对齐 Pandoc 官方实现：
 * - 尺寸单位规则（MANUAL "Extension: link_attributes" + src/Text/Pandoc/ImageSize.php toDim）：
 *   无单位 / px → 整数像素，输出 width/height 属性；%、cm、mm、in、inch、pt、pc、em、ex
 *   → 输出 style="width:..%;height:..cm"，多个声明以 `;` 合并（Writers/HTML.hs
 *   dimensionsToAttrList）。无效尺寸与 Pandoc 一致直接丢弃。
 * - `#id` → id 属性（净化端需 Attr.EnableID）；`.class` 与 `class="a b"` 合并进 class。
 * - `alt="..."` 覆盖 alt 文本（MANUAL 图注一节）；`title="..."` 覆盖 title。
 *
 * 与 Pandoc 的已知差异（刻意收敛，均有注释说明）：
 * 1. 仅作用于紧跟在图片/链接之后的 `{...}` 块（peek(-1) 非空白且前一节点是
 *    AbstractWebResource），不启用 commonmark AttributesExtension 全局扩展——后者会把
 *    普通正文里的 `{k=v}` 也吞掉，静默改写存量文章（已实测复现）。
 * 2. 未知键（如 `data-foo`、`loading`）直接忽略而非 Pandoc 的 `data-` 前缀透传：
 *    HTMLPurifier 默认剥离 data-*，透传无意义。
 * 3. 标识符（id/class/key）限定 ASCII：`[\w:.-]`；引号包裹的值可含任意字节。
 * 4. 扩展支持 CSS 风格冒号语法 `{width:70%}`（Pandoc 仅 `=`）。为防吞掉图片后的
 *    普通说明文本（如 `{note: hello}`），冒号键限定白名单 COLON_KEYS；`=` 形式
 *    保持 Pandoc 原样（任意键，未知键消费后忽略）。
 *
 * @see https://raw.githubusercontent.com/jgm/pandoc/main/MANUAL.txt "Extension: link_attributes"
 * @see https://github.com/jgm/pandoc/blob/main/src/Text/Pandoc/ImageSize.php toDim()
 * @see https://github.com/jgm/pandoc/blob/main/src/Text/Pandoc/Writers/HTML.hs dimensionsToAttrList()
 */
final class PandocAttributesParser implements InlineParserInterface
{
    /**
     * 整个 `{...}` 块一次性匹配（对齐 AttributesHelper 的做法：先锁定首尾花括号，
     * 避免逐 token 匹配时被值内部字符干扰）。块结构不合法则整体不消费，按字面量输出。
     * 冒号形式键限定白名单（见类注释差异 4）。
     */
    private const BLOCK = '/\G\{[ \t]*(?:(?:#[\w:.-]+|\.[\w:.-]+|[A-Za-z][\w-]*=(?:"[^"\n]*"|\'[^\'\n]*\'|[^\s{}"\'=]+)|(?:width|height|style|class|id|alt|title)[ \t]*:[ \t]*(?:"[^"\n]*"|\'[^\'\n]*\'|[^\s{}"\'=]+))[ \t]*)*\}/';

    /**
     * 块内逐 token 拆解（BLOCK 已保证结构合法，此处提取各组值）。
     * 组：1=#id 2=.class 3==键 4==值 5=:键 6=:值（冒号形式仅白名单键）。
     */
    private const TOKEN = '/(?:#([\w:.-]+))|(?:\.([\w:.-]+))|(?:([A-Za-z][\w-]*)=("[^"\n]*"|\'[^\'\n]*\'|[^\s{}"\'=]+))|((?:width|height|style|class|id|alt|title))[ \t]*:[ \t]*("[^"\n]*"|\'[^\'\n]*\'|[^\s{}"\'=]+)/';

    /** Pandoc ImageSize.php toDim() 支持的单位（大小写不敏感；数字与单位间不允许空格）。注意无 ex。 */
    private const DIMENSION = '/\A(\d+(?:\.\d+)?)(inch|in|cm|mm|pt|pc|px|em|%)\z/i';

    /** 不带单位时的纯数字尺寸（Pandoc 默认像素） */
    private const DIMENSION_PIXEL = '/\A(\d+(?:\.\d+)?)\z/';

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::string('{');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();

        // Pandoc MANUAL「Attributes」：属性紧跟元素才属于该元素，隔空格则属于空格/文本。
        // 同时保证存量正文里的普通 `{...}` 文本（前一节点是 Text）不受影响。
        $previous = $cursor->peek(-1);
        if ($previous === null || \preg_match('/\s/', $previous) === 1) {
            return false;
        }

        $target = $inlineContext->getContainer()->lastChild();
        if (! $target instanceof AbstractWebResource) {
            return false;
        }

        $state = $cursor->saveState();
        $block = $cursor->matchInPlace(self::BLOCK);
        if ($block === null) {
            $cursor->restoreState($state);
            return false;
        }

        $attributes = $this->parseTokens(\substr($block, 1, -1));
        if ($attributes === null) {
            // BLOCK 匹配成功但 token 化失败（防御分支）：回滚，保持 `{` 字面量输出
            $cursor->restoreState($state);
            return false;
        }

        $this->apply($target, $attributes);

        return true;
    }

    /**
     * @return ParsedAttributes|null null = 结构非法（非空白残留），整块不应被消费
     */
    private function parseTokens(string $inner): ?ParsedAttributes
    {
        /** @var list<array<int, string>> $matches */
        $matches = [];
        $count = \preg_match_all(self::TOKEN, $inner, $matches, \PREG_SET_ORDER);
        if ($count === false) {
            return null;
        }
        if ($count === 0) {
            // `{}` 空属性块是合法写法；除此之外的零匹配视为非法
            return \trim($inner) === '' ? new ParsedAttributes(null, [], []) : null;
        }

        $id = null;
        $classes = [];
        $keyValues = [];
        foreach ($matches as $match) {
            if (($match[1] ?? '') !== '') {
                $id = $match[1];
                continue;
            }
            if (($match[2] ?? '') !== '') {
                $classes[] = $match[2];
                continue;
            }
            // 组3/4 = `key=value`；组5/6 = `key: value`（冒号形式，白名单键）
            if (($match[5] ?? '') !== '') {
                $key = $match[5];
                $value = $match[6];
            } else {
                $key = $match[3];
                $value = $match[4];
            }
            if (isset($value[0]) && ($value[0] === '"' || $value[0] === "'")) {
                $value = \substr($value, 1, -1);
            }
            $keyValues[$key] = $value;
        }

        return new ParsedAttributes($id, $classes, $keyValues);
    }

    private function apply(AbstractWebResource $node, ParsedAttributes $attributes): void
    {
        /** @var array<string, mixed> $attrs */
        $attrs = (array) $node->data->get('attributes');

        $classes = $attributes->classes;
        $style = [];
        $id = $attributes->id;

        foreach ($attributes->keyValues as $key => $value) {
            switch ($key) {
                case 'width':
                case 'height':
                    [$attrValue, $styleValue] = $this->parseDimension($value) ?? [null, null];
                    if ($attrValue !== null) {
                        $attrs[$key] = $attrValue;
                    } elseif ($styleValue !== null) {
                        $style[] = $key . ':' . $styleValue;
                    }
                    break;
                case 'style':
                    $style[] = $value;
                    break;
                case 'class':
                    foreach (\array_filter(\explode(' ', $value)) as $class) {
                        $classes[] = $class;
                    }
                    break;
                case 'id':
                    $id = $value;
                    break;
                case 'alt':
                    // commonmark 的 ImageRenderer 用子节点字面量拼 alt，会覆盖 data attributes，
                    // 因此必须改写子节点才能实现 Pandoc 的 alt 覆盖语义
                    if ($node instanceof Image) {
                        $node->detachChildren();
                        $node->appendChild(new Text($value));
                    }
                    break;
                case 'title':
                    // setTitle 不在 AbstractWebResource 基类上，Image/Link 各自声明
                    if ($node instanceof Image || $node instanceof Link) {
                        $node->setTitle($value);
                    }
                    break;
                default:
                    // 未知键忽略（见类注释差异 2）
                    break;
            }
        }

        if ($id !== null && $id !== '') {
            $attrs['id'] = $id;
        }
        if ($classes !== []) {
            $existing = isset($attrs['class']) ? \explode(' ', (string) $attrs['class']) : [];
            $attrs['class'] = \implode(' ', \array_unique(\array_merge($existing, $classes)));
        }
        if ($style !== []) {
            $attrs['style'] = \implode(';', $style);
        }

        $node->data->set('attributes', $attrs);
    }

    /**
     * 尺寸值 → [像素属性值, style 声明]，二选一（另一项为 null；整体无效返回 null，
     * 与 Pandoc 丢弃语义一致）。
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function parseDimension(string $value): ?array
    {
        if (\preg_match(self::DIMENSION_PIXEL, $value, $m) === 1) {
            return [(string) (int) \round((float) $m[1]), null];
        }
        if (\preg_match(self::DIMENSION, $value, $m) !== 1) {
            return null;
        }
        $number = $m[1];
        $unit = \strtolower($m[2]);
        if ($unit === 'px') {
            return [(string) (int) \round((float) $number), null];
        }
        return [null, $number . $unit];
    }
}
