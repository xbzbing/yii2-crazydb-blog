<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Post\MarkdownRenderer;
use App\Tests\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;

/**
 * Pandoc `fenced_divs` 围栏 div 扩展（前后端语义的后端半边）。
 *
 * @see https://raw.githubusercontent.com/jgm/pandoc/main/MANUAL.txt "Extension: fenced_divs"
 */
final class PandocFencedDivTest extends TestCase
{
    private function renderer(): MarkdownRenderer
    {
        return new MarkdownRenderer(new Cache(new ArrayCache()));
    }

    public function testBasicDivWithClassWrapsContent(): void
    {
        $html = $this->renderer()->render("::: {.center}\n![alt](/upload/a.jpg){width=70%}\n:::\n\n普通段落", 1);
        self::assertStringContainsString('<div class="center">', $html);
        self::assertStringContainsString('style="width:70%;"', $html, 'div 内的图片属性照常生效');
        self::assertStringNotContainsString(':::', $html, '围栏行不得以字面量输出');
        self::assertStringContainsString('<p>普通段落</p>', $html);
        // 闭合结构：div 结束在后，普通段落在 div 之外
        self::assertLessThan(
            \strpos($html, '<p>普通段落</p>'),
            \strpos($html, '</div>'),
            'closing div must precede the following paragraph',
        );
    }

    public function testFenceOpensWithoutBlankLineAfterParagraph(): void
    {
        $html = $this->renderer()->render("前一段\n::: {.center}\n紧贴内容\n:::\n后一段", 1);
        self::assertStringContainsString('<p>前一段</p>', $html);
        self::assertStringContainsString('<div class="center">', $html);
        self::assertStringContainsString('<p>紧贴内容</p>', $html);
        self::assertStringContainsString('<p>后一段</p>', $html);
    }

    public function testIdAndClassAttributes(): void
    {
        $html = $this->renderer()->render("::: {.warning #note1}\n正文\n:::", 1);
        self::assertStringContainsString('id="note1"', $html);
        self::assertStringContainsString('class="warning"', $html);
    }

    public function testColonFormAttributes(): void
    {
        $html = $this->renderer()->render("::: {class: center id: box2}\n正文\n:::", 1);
        self::assertStringContainsString('class="center"', $html);
        self::assertStringContainsString('id="box2"', $html);
    }

    public function testStyleAndTitleAttributes(): void
    {
        $html = $this->renderer()->render('::: {style="text-align:center" title="提示"}' . "\n居中块\n:::", 1);
        self::assertStringContainsString('text-align:center', $html);
        self::assertStringContainsString('title="提示"', $html);
    }

    public function testNestedDivsWithEqualFences(): void
    {
        $md   = "::: {.outer}\n上层\n\n::: {.inner}\n内层\n:::\n\n回到上层\n:::";
        $html = $this->renderer()->render($md, 1);
        self::assertStringContainsString('<div class="outer">', $html);
        self::assertStringContainsString('<div class="inner">', $html);
        self::assertStringNotContainsString(':::', $html, '等长嵌套的内层闭栏必须被消费');

        $outerOpen  = \strpos($html, '<div class="outer">');
        $innerOpen  = \strpos($html, '<div class="inner">');
        $innerClose = \strpos($html, '</div>'); // 第一个 </div> 属于内层
        $backToTop  = \strpos($html, '<p>回到上层</p>');
        $outerClose = \strrpos($html, '</div>'); // 最后一个属于外层
        self::assertNotFalse($outerOpen);
        self::assertNotFalse($innerOpen);
        self::assertNotFalse($innerClose);
        self::assertNotFalse($backToTop);
        self::assertNotFalse($outerClose);
        self::assertTrue(
            $outerOpen < $innerOpen && $innerOpen < $innerClose && $innerClose < $backToTop && $backToTop < $outerClose,
            '结构序必须为 outer开 → inner开 → inner闭 → 回到上层(在外层内) → outer闭',
        );
    }

    public function testLongerClosingFenceAllowed(): void
    {
        $html = $this->renderer()->render(":::: {.big}\n外层\n\n::: {.small}\n内层\n:::\n::::", 1);
        self::assertStringContainsString('<div class="big">', $html);
        self::assertStringContainsString('<div class="small">', $html);
        self::assertStringNotContainsString(':::', $html);
    }

    public function testStrayClosingFenceStaysLiteral(): void
    {
        $html = $this->renderer()->render("游离的闭栏 ::: 单独成段", 1);
        self::assertStringContainsString(':::', $html);
        self::assertStringNotContainsString('<div', $html, '无开栏的裸 ::: 不得开 div');
    }

    public function testOpeningFenceRequiresAttributeBlock(): void
    {
        $html = $this->renderer()->render(":::\n裸围栏不开 div\n:::", 1);
        self::assertStringNotContainsString('<div', $html);
        self::assertStringContainsString(':::', $html, '裸 ::: 行整体保持字面量');
    }

    public function testInvalidAttributeBlockStaysLiteral(): void
    {
        $html = $this->renderer()->render("::: {note: hello}\n这行保持字面量\n:::", 1);
        self::assertStringNotContainsString('<div', $html, '非白名单冒号键不构成合法属性块');
        self::assertStringContainsString('::: {note: hello}', $html);
    }

    public function testAttributesMustBeOnFenceLine(): void
    {
        $html = $this->renderer()->render(":::\n{.center}\n内容\n:::", 1);
        self::assertStringNotContainsString('<div', $html, '属性块换行写不支持（须在开栏同行）');
    }

    public function testFenceInsideFencedCodeUntouched(): void
    {
        $html = $this->renderer()->render("```\n::: {.center}\n```", 1);
        self::assertStringContainsString('::: {.center}', $html);
        self::assertStringNotContainsString('<div', $html);
    }

    public function testLazyParagraphLinesInsideDiv(): void
    {
        $html = $this->renderer()->render("::: {.box}\n第一行\n第二行\n:::", 1);
        self::assertStringContainsString("<p>第一行\n第二行</p>", $html);
    }

    public function testBlocksInsideDiv(): void
    {
        $html = $this->renderer()->render("::: {.box}\n## 标题\n\n- 项目一\n- 项目二\n:::", 1);
        self::assertStringContainsString('<div class="box">', $html);
        self::assertStringContainsString('<h2', $html);
        self::assertStringContainsString('<li>项目一</li>', $html);
    }

    public function testUnclosedDivWrappedToDocumentEnd(): void
    {
        $html = $this->renderer()->render("::: {.center}\n到结束都没闭合", 1);
        self::assertStringContainsString('<div class="center">', $html);
        self::assertStringContainsString('到结束都没闭合', $html);
        self::assertStringContainsString('</div>', $html, 'HTMLPurifier 必须兜底闭合标签');
    }

    public function testEmptyAttributeBlockOpensPlainDiv(): void
    {
        $html = $this->renderer()->render("::: {}\n裸 div\n:::", 1);
        self::assertStringContainsString('<div>', $html);
    }

    public function testCacheKeyBumpedToMdv4(): void
    {
        // fenced_divs 改变存量渲染语义（::: 由字面量变 div 包裹）→ 前缀必须 bump 到 mdv4。
        // 在 mdv3 前缀下预埋哨兵：若实现仍用旧前缀，render() 会直接吐出哨兵。
        $arrayCache = new ArrayCache();
        $markdown  = "::: {.center}\n正文\n:::";
        $arrayCache->set('mdv3.1.' . \sha1($markdown), '<p>STALE-SENTINEL</p>');

        $html = (new MarkdownRenderer(new Cache($arrayCache)))->render($markdown, 1);
        self::assertStringNotContainsString('STALE-SENTINEL', $html);
        self::assertStringContainsString('<div class="center">', $html);
    }
}
