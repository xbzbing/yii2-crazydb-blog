<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Post\HtmlToMarkdownService;
use App\Tests\TestCase;

final class HtmlToMarkdownServiceTest extends TestCase
{
    private HtmlToMarkdownService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HtmlToMarkdownService();
    }

    public function testPlainTextPassesThrough(): void
    {
        self::assertSame("just plain text, no tags\n", $this->service->convert('just plain text, no tags'));
    }

    public function testEmptyInput(): void
    {
        self::assertSame('', trim($this->service->convert('')));
        self::assertSame('', trim($this->service->convert('<p></p>')));
    }

    public function testBrushBlockConvertsToFencedCode(): void
    {
        $md = $this->service->convert(
            '<p>text</p><pre class="brush:php;toolbar:false">echo &quot;hi&quot;;</pre><p>after</p>',
        );
        self::assertStringContainsString("```php\n", $md);
        self::assertStringContainsString('echo "hi";', $md);
        self::assertStringContainsString('text', $md);
        self::assertStringContainsString('after', $md);
        // 转换后不再残留 brush: / class= 原始标记
        self::assertStringNotContainsString('brush:', $md);
        self::assertStringNotContainsString('class="brush', $md);
    }

    public function testBrushTsMapsToHighlightJsTypescript(): void
    {
        $md = $this->service->convert(
            '<pre class="brush:ts;toolbar:false">const x: number = 1;</pre>',
        );
        self::assertStringContainsString('```typescript', $md);
    }

    public function testBrushUnknownLanguageFallsBackToLowercase(): void
    {
        $md = $this->service->convert(
            '<pre class="brush:CoBOL;toolbar:false">DISPLAY &quot;hi&quot;</pre>',
        );
        // BRUSH_MAP 没有 cobol → 回退小写原名
        self::assertStringContainsString('```cobol', $md);
    }

    public function testSpanUnwrappedStyleStripped(): void
    {
        self::assertSame("Hello\n", $this->service->convert('<span style="color:#f00"><p>Hello</p></span>'));
    }

    public function testParagraphStyleStrippedStrongKept(): void
    {
        self::assertSame(
            "你好 **世界**\n",
            $this->service->convert('<p style="font-size:16px; line-height:1.8;">你好 <strong>世界</strong></p>'),
        );
    }

    public function testImageStyleWidthHeightStripped(): void
    {
        $md = $this->service->convert(
            '<p style="text-align:center;"><img src="/u/1.jpg" width="640" height="360" style="width: 640px;" /></p>',
        );
        self::assertSame("![](/u/1.jpg)\n", $md);
    }

    public function testEscapedEntitiesPreserved(): void
    {
        // 已转义的实体在正文中保持转义，避免 markdown 语法被正文混排字符破坏
        self::assertSame(
            "&lt;script&gt;alert(1)&lt;/script&gt; 1 &lt; 2\n",
            $this->service->convert('<p>&lt;script&gt;alert(1)&lt;/script&gt; 1 &lt; 2</p>'),
        );
    }

    public function testUEditorTypicalStructure(): void
    {
        $md = $this->service->convert(
            '<p style="text-align:center;"><img src="/u/1.jpg" width="640" height="360" style="width: 640px;"/></p>'
            . '<p>段落 <em>强调</em></p>'
            . '<pre class="brush:ts;toolbar:false">const x: number = 1;</pre>',
        );
        self::assertStringContainsString('![](/u/1.jpg)', $md);
        self::assertStringContainsString('段落 *强调*', $md);
        self::assertStringContainsString('```typescript', $md);
        self::assertStringNotContainsString('style=', $md);
        self::assertStringNotContainsString('width=', $md);
    }

    public function testHeadingConvertsToMarkdown(): void
    {
        $md = $this->service->convert('<h2>Chapter</h2><p>body</p>');
        self::assertStringContainsString('Chapter', $md);
        self::assertStringContainsString('body', $md);
    }

    public function testConsecutiveBlankLinesCompressed(): void
    {
        // 样式块剥离后可能残留连续空行，应压缩为最多一个空行
        $md = $this->service->convert("<p>a</p><span style=\"color:#fff\"></span><p>b</p>");
        self::assertStringNotContainsString("\n\n\n", $md);
    }

    public function testTableAndHtmlBlockSurvive(): void
    {
        $md = $this->service->convert('<table><tr><td>1</td></tr></table><p>after</p>');
        self::assertStringContainsString('after', $md);
        self::assertStringContainsString('1', $md);
    }
}
