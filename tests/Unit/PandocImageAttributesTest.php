<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Post\MarkdownRenderer;
use App\Tests\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;

/**
 * Pandoc link_attributes 图片扩展语法（前后端同源语义的后端半边）。
 *
 * @see https://raw.githubusercontent.com/jgm/pandoc/main/MANUAL.txt "Extension: link_attributes"
 */
final class PandocImageAttributesTest extends TestCase
{
    private function renderer(): MarkdownRenderer
    {
        return new MarkdownRenderer(new Cache(new ArrayCache()));
    }

    public function testUnitlessWidthHeightRenderAsPixelAttributes(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){width=300 height=200}', 1);
        self::assertStringContainsString('width="300"', $html);
        self::assertStringContainsString('height="200"', $html);
        self::assertStringNotContainsString('{', $html, 'attribute block must not leak as literal text');
    }

    public function testPxUnitRendersAsPixelAttribute(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){width=300px height=20px}', 1);
        self::assertStringContainsString('width="300"', $html);
        self::assertStringContainsString('height="20"', $html);
        self::assertStringNotContainsString('300px', $html);
    }

    public function testPercentSizeRendersAsStyle(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){width=50%}', 1);
        self::assertStringContainsString('style="width:50%;"', $html);
        self::assertStringNotContainsString('width="50"', $html);
    }

    public function testCmSizeRendersAsStyle(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){height=10cm}', 1);
        self::assertStringContainsString('style="height:10cm;"', $html);
    }

    public function testMixedUnitsConsolidateIntoOneStyle(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){width=50% height=200}', 1);
        self::assertStringContainsString('style="width:50%;"', $html);
        self::assertStringContainsString('height="200"', $html);
    }

    public function testExistingStyleKeyMergesWithSizeStyles(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){style="border:1px solid" width=50%}', 1);
        self::assertStringContainsString('border:1px solid;width:50%;', $html);
    }

    public function testIdAndClassSurvivePurify(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){#fig1 .left .rounded}', 1);
        self::assertStringContainsString('id="fig1"', $html);
        self::assertStringContainsString('class="left rounded"', $html);
    }

    public function testClassKeyMergesWithDotClasses(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){.left class="big shadow"}', 1);
        self::assertStringContainsString('class="left big shadow"', $html);
    }

    public function testAltOverrideReplacesAltText(): void
    {
        $html = $this->renderer()->render('![原始说明](/upload/a.jpg){alt="新说明"}', 1);
        self::assertStringContainsString('alt="新说明"', $html);
        self::assertStringNotContainsString('原始说明', $html);
    }

    public function testTitleOverride(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg "旧标题"){title="新标题"}', 1);
        self::assertStringContainsString('title="新标题"', $html);
        self::assertStringNotContainsString('旧标题', $html);
    }

    public function testQuotedValueWithSpaces(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){alt="带 空格 的说明"}', 1);
        self::assertStringContainsString('alt="带 空格 的说明"', $html);
    }

    public function testInvalidDimensionDroppedNotLeaked(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){width=abc}', 1);
        self::assertStringNotContainsString('width="abc"', $html);
        self::assertStringNotContainsString('{', $html, 'invalid dimension is dropped but block still consumed');
    }

    public function testUnknownKeysIgnored(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){loading=eager width=100}', 1);
        self::assertStringContainsString('width="100"', $html);
        self::assertStringNotContainsString('loading', $html);
        self::assertStringNotContainsString('{', $html);
    }

    public function testMalformedBlockKeepsLiteralBraces(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){width=}', 1);
        self::assertStringContainsString('{width=}', $html, 'malformed attribute block must stay literal');
    }

    public function testPlainTextBracesAfterTextNotConsumed(): void
    {
        $html = $this->renderer()->render('正文 {width=30} 后面还有字', 1);
        self::assertStringContainsString('{width=30}', $html, 'plain-text braces must not be consumed');
        self::assertStringNotContainsString('width="30"', $html);
    }

    public function testPlainTextBracesImmediatelyAfterTextNotConsumed(): void
    {
        $html = $this->renderer()->render('正文{width=30}', 1);
        self::assertStringContainsString('{width=30}', $html, 'previous sibling is text, not image/link');
    }

    public function testBracesAfterSpaceNotAppliedToImage(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg) {width=30}', 1);
        self::assertStringContainsString('{width=30}', $html, 'space separator belongs to the space (Pandoc rule)');
        self::assertStringNotContainsString('width="30"', $html);
    }

    public function testCodeBlockBracesUntouched(): void
    {
        $html = $this->renderer()->render("```\n![a](b.jpg){width=30}\n```", 1);
        self::assertStringContainsString('{width=30}', $html, 'code blocks are literal');
        self::assertStringNotContainsString('width="30"', $html);
    }

    public function testInlineCodeBracesUntouched(): void
    {
        $html = $this->renderer()->render('`![a](b.jpg){width=30}`', 1);
        self::assertStringContainsString('{width=30}', $html);
        self::assertStringNotContainsString('width="30"', $html);
    }

    public function testAttributesOnLink(): void
    {
        $html = $this->renderer()->render('[链接](https://example.com/){#lnk .btn}', 1);
        self::assertStringContainsString('id="lnk"', $html);
        self::assertStringContainsString('class="btn"', $html);
    }

    public function testMultipleImagesEachGetOwnAttributes(): void
    {
        $html = $this->renderer()->render(
            '![a](/upload/a.jpg){width=100} 中间 ![b](/upload/b.jpg){width=200}',
            1,
        );
        self::assertStringContainsString('width="100" src="/upload/a.jpg"', $html);
        self::assertStringContainsString('width="200" src="/upload/b.jpg"', $html);
    }

    public function testEmptyAttributeBlockConsumed(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){}', 1);
        self::assertStringNotContainsString('{}', $html);
        self::assertStringContainsString('src="/upload/a.jpg"', $html);
    }

    public function testXssQuoteBreakoutInAltIsEscaped(): void
    {
        // 引号注入尝试：值内的 " 必须被转义为 &quot;，不能逃出 alt 属性
        $html = $this->renderer()->render('![alt](/upload/a.jpg){alt=\'a" onerror="alert(1)\'}', 1);
        self::assertStringContainsString('alt="a&quot; onerror=&quot;alert(1)"', $html);
    }

    public function testMalformedQuotedBlockStaysLiteralAndImgStaysClean(): void
    {
        // 未闭合引号 → 整块解析失败 → 花括号按字面量输出；img 标签本身不含任何注入属性
        $html = $this->renderer()->render('![alt](/upload/a.jpg){alt="x\\" onerror=alert(1) x="}', 1);
        self::assertStringContainsString('<img src="/upload/a.jpg" alt="alt" />', $html);
        self::assertStringContainsString('{alt=', $html, 'malformed block falls back to literal text');
    }

    public function testColonWidthPercentRendersAsStyle(): void
    {
        // CSS 风格冒号语法（用户实际书写习惯，Pandoc 原生仅 =）
        $html = $this->renderer()->render('![alt](/upload/a.jpg){width:70%}', 1);
        self::assertStringContainsString('style="width:70%;"', $html);
        self::assertStringNotContainsString('{', $html);
    }

    public function testColonWithSpacesAndMixedEquals(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){width: 50% height:200px border=0}', 1);
        self::assertStringContainsString('style="width:50%;"', $html);
        self::assertStringContainsString('height="200"', $html);
        self::assertStringNotContainsString('{', $html);
    }

    public function testColonAltAndTitleOverride(): void
    {
        $html = $this->renderer()->render('![原始](/upload/a.jpg){alt: 新说明 title:"新标题"}', 1);
        self::assertStringContainsString('alt="新说明"', $html);
        self::assertStringContainsString('title="新标题"', $html);
        self::assertStringNotContainsString('原始', $html);
    }

    public function testColonIdAndClass(): void
    {
        $html = $this->renderer()->render('![alt](/upload/a.jpg){#fig2 .left class: "big shadow"}', 1);
        self::assertStringContainsString('id="fig2"', $html);
        self::assertStringContainsString('class="left big shadow"', $html);
    }

    public function testColonUnknownKeyAfterImageStaysLiteral(): void
    {
        // 冒号键白名单保护：图片后的普通说明文本 {note: hello} 不被吞
        $html = $this->renderer()->render('![alt](/upload/a.jpg){note: hello}', 1);
        self::assertStringContainsString('{note: hello}', $html, 'unknown colon key must stay literal');
        self::assertStringNotContainsString('width=', $html);
    }

    public function testColonPlainTextAfterTextNotConsumed(): void
    {
        $html = $this->renderer()->render('正文 {width:70%} 后文', 1);
        self::assertStringContainsString('{width:70%}', $html);
        self::assertStringNotContainsString('style="width:70%;"', $html);
    }

    public function testCacheKeyBumpRendersColonSemantics(): void
    {
        // 冒号支持改变存量渲染语义 → 缓存前缀必须 bump（现行 mdv4，历史 mdv3/mdv2），
        // 否则旧缓存回吐字面量；本用例走 renderPost 验证新前缀下语义正确
        $post = new \App\Post\Post();
        $post->format = \App\Post\Post::FORMAT_MARKDOWN;
        $post->content = '![alt](/upload/a.jpg){width:70%}';
        $post->update_time = 1700000000;
        $html = $this->renderer()->renderPost($post);
        self::assertStringContainsString('style="width:70%;"', $html);
    }

    public function testImageInHtmlFormatPostUnaffected(): void
    {
        $post = new \App\Post\Post();
        $post->format = \App\Post\Post::FORMAT_HTML;
        $post->content = '<p><img src="/a.jpg" width="300"></p>';
        $post->update_time = 1700000000;
        $html = $this->renderer()->renderPost($post);
        self::assertStringContainsString('width="300"', $html);
    }

    public function testCacheKeyBumpRendersNewSemantics(): void
    {
        $post = new \App\Post\Post();
        $post->format = \App\Post\Post::FORMAT_MARKDOWN;
        $post->content = '![alt](/upload/a.jpg){width=300}';
        $post->update_time = 1700000000;
        $html = $this->renderer()->renderPost($post);
        self::assertStringContainsString('width="300"', $html);
    }
}
