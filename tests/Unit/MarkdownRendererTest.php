<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Post\MarkdownRenderer;
use App\Post\Post;
use App\Tests\TestCase;
use DateInterval;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\Dependency\Dependency;

final class MarkdownRendererTest extends TestCase
{
    private function renderer(): MarkdownRenderer
    {
        return new MarkdownRenderer(new Cache(new ArrayCache()));
    }

    public function testGfmTableRenders(): void
    {
        $md = "| a | b |\n|---|---|\n| 1 | 2 |";
        $html = $this->renderer()->render($md, 1);
        self::assertStringContainsString('<table>', $html);
        self::assertStringContainsString('<th>a</th>', $html);
        self::assertStringContainsString('<td>2</td>', $html);
    }

    public function testTaskListRenders(): void
    {
        $html = $this->renderer()->render("- [x] done\n- [ ] todo", 1);
        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringContainsString('checked', $html);
    }

    public function testStrikethroughRenders(): void
    {
        $html = $this->renderer()->render('~~gone~~', 1);
        self::assertStringContainsString('<del>gone</del>', $html);
    }

    public function testFencedCodeRendersHighlightJsFormat(): void
    {
        $html = $this->renderer()->render("```php\necho 'hi';\n```", 1);
        self::assertStringContainsString('<pre><code class="language-php">', $html);
        self::assertStringContainsString("echo 'hi';", $html);
    }

    public function testFencedCodeEscapesContentAgainstInjection(): void
    {
        $html = $this->renderer()->render("```js\n</pre><script>alert(1)</script>\n```", 1);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;/pre&gt;', $html);
    }

    public function testCodeWithoutLanguageOmitsClass(): void
    {
        $html = $this->renderer()->render("```\nplain text\n```", 1);
        self::assertStringContainsString('<pre><code>', $html);
        self::assertStringNotContainsString('class=', $html);
    }

    public function testPurifiesUnsafeHtml(): void
    {
        $html = $this->renderer()->render("# t\n\n<script>alert(1)</script>\n\n<iframe src=x></iframe>", 1);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('<iframe', $html);
    }

    public function testKeepsImgAndCodeClassAfterPurify(): void
    {
        $html = $this->renderer()->render("![alt](/upload/a.png)\n\n```php\nx\n```", 1);
        self::assertStringContainsString('src="/upload/a.png"', $html);
        self::assertStringContainsString('<pre><code class="language-php">', $html);
    }

    public function testHtmlFormatPostRendersAsIsWithPurify(): void
    {
        $post = new Post();
        $post->format = Post::FORMAT_HTML;
        $post->content = '<p>legacy</p><script>alert(1)</script>';
        $html = $this->renderer()->renderPost($post);
        self::assertStringContainsString('<p>legacy</p>', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    public function testHtmlFormatPostNotRewrittenByMarkdownSyntax(): void
    {
        $post = new Post();
        $post->format = Post::FORMAT_HTML;
        $post->content = '<p>foo_bar 变量、*星号*、~~删除线~~ 保持原样</p>';
        $post->update_time = 1700000000;
        $html = $this->renderer()->renderPost($post);
        self::assertStringContainsString('foo_bar', $html, 'underscores must not become em');
        self::assertStringContainsString('*星号*', $html, 'asterisks must not become em');
        self::assertStringContainsString('~~删除线~~', $html, 'tildes must not become del');
        self::assertStringNotContainsString('<em>', $html);
        self::assertStringNotContainsString('<del>', $html);
    }

    public function testMarkdownFormatPostRendersThroughPipeline(): void
    {
        $post = new Post();
        $post->format = Post::FORMAT_MARKDOWN;
        $post->content = "## md title\n\n```php\nx\n```";
        $post->update_time = 1700000000;
        $html = $this->renderer()->renderPost($post);
        self::assertStringContainsString('<h2>md title</h2>', $html);
        self::assertStringContainsString('<pre><code class="language-php">', $html);
    }

    public function testCacheHitSkipsRenderingAndVersionChangesInvalidate(): void
    {
        $cache = new class() implements CacheInterface {
            public int $sets = 0;
            private Cache $inner;

            public function __construct()
            {
                $this->inner = new Cache(new ArrayCache());
            }

            public function psr(): \Psr\SimpleCache\CacheInterface
            {
                return $this->inner->psr();
            }

            public function getOrSet(
                mixed $key,
                callable $callable,
                DateInterval|int|null $ttl = null,
                ?Dependency $dependency = null,
                float $beta = 1.0,
            ): mixed {
                return $this->inner->getOrSet($key, function () use ($callable) {
                    $this->sets++;
                    return $callable();
                }, $ttl, $dependency, $beta);
            }

            public function remove(mixed $key): void
            {
                $this->inner->remove($key);
            }
        };
        $renderer = new MarkdownRenderer($cache);

        $first = $renderer->render('# cached', 1);
        $second = $renderer->render('# cached', 1);
        self::assertSame($first, $second, 'same version + content should hit cache');
        self::assertSame(1, $cache->sets, 'second call must not re-render');

        $renderer->render('# cached', 2);
        self::assertSame(2, $cache->sets, 'update_time change must invalidate cache');
    }

    public function testAttachTocAnchorsExtractsHeadings(): void
    {
        $renderer = $this->renderer();
        $html = $renderer->render("# 标题一\n\n正文\n\n## 小节 A\n\n内容\n\n### 小节 A1\n\n内容\n\n## 小节 B\n\n内容", 1);
        $toc = $renderer->attachTocAnchors($html);
        self::assertCount(3, $toc, 'h2/h3 collected; h1 anchored but not in toc');
        self::assertSame('toc-2', $toc[0]['id'], 'h1 consumes toc-1 anchor without entering toc');
        self::assertSame(2, $toc[0]['level']);
        self::assertSame('小节 A', $toc[0]['text']);
        self::assertSame('小节 B', $toc[2]['text']);
        self::assertStringContainsString('id="toc-1"', $html, 'h1 still gets anchor id');
        self::assertStringContainsString('id="toc-4"', $html);
    }

    public function testAttachTocAnchorsSkipsExistingIdsAndEscapesText(): void
    {
        $renderer = $this->renderer();
        $html = '<h2 id="keep">已有 ID</h2><h2 title="id=z">属性值含 id=</h2><h2>含 <b>加粗</b> 与 &amp; 符号</h2>';
        $toc = $renderer->attachTocAnchors($html);
        self::assertStringContainsString('id="keep"', $html, 'existing id untouched');
        self::assertStringContainsString('id="toc-2"', $html, 'title="id=z" must not block anchor');
        self::assertCount(2, $toc, 'existing id heading not duplicated; title-value heading collected');
        self::assertSame('含 加粗 与 & 符号', $toc[1]['text'], 'text stripped of tags and entities decoded');
    }

    public function testGetCoverImageFromMarkdown(): void
    {
        $post = new Post();
        $post->format = Post::FORMAT_MARKDOWN;
        $post->content = "# t\n\n![cover](/upload/2026/08/cover.png)\n\nbody";
        $post->update_time = 1700000000;
        self::assertSame('/upload/2026/08/cover.png', $post->getCoverImage($this->renderer()));
    }

    public function testGetSeoDescriptionStripsTagsAndTruncates(): void
    {
        $post = new Post();
        $post->format = Post::FORMAT_MARKDOWN;
        $post->content = "# t\n\n正文开始 **bold** 继续";
        $post->update_time = 1700000000;
        $seo = $post->getSeoDescription($this->renderer(), 10);
        self::assertStringNotContainsString('#', $seo);
        self::assertStringNotContainsString('**', $seo);
        self::assertStringEndsWith('...', $seo);
    }
}
