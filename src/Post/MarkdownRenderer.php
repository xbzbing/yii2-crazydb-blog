<?php

declare(strict_types=1);

namespace App\Post;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Yiisoft\Cache\CacheInterface;

/**
 * Markdown 渲染管线：commonmark(GFM) + SyntaxHighlighter 兼容代码块 + HTMLPurifier 净化。
 *
 * 输出缓存放 Redis，key 由 update_time 与内容 hash 构成——文章更新（update_time 变化）
 * 即自动失效，无需显式清缓存。
 */
final class MarkdownRenderer
{
    private const CACHE_TTL = 30 * 24 * 60 * 60;

    private readonly MarkdownConverter $converter;

    public function __construct(private readonly CacheInterface $cache)
    {
        $environment = new Environment([
            'html_input' => 'allow',
            'max_nesting_level' => 32,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new FootnoteExtension());
        $environment->addRenderer(
            \League\CommonMark\Extension\CommonMark\Node\Block\FencedCode::class,
            new SyntaxHighlighterCodeRenderer(),
        );
        $environment->addRenderer(
            \League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode::class,
            new SyntaxHighlighterCodeRenderer(),
        );
        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * 渲染 markdown 为净化后的 HTML。
     *
     * @param int $version 内容版本号（update_time），参与缓存 key 实现文章更新即失效
     */
    public function render(string $markdown, int $version = 0): string
    {
        $key = 'md.' . $version . '.' . sha1($markdown);
        /** @var string $html */
        $html = $this->cache->getOrSet(
            $key,
            fn (): string => $this->renderUncached($markdown),
            self::CACHE_TTL,
        );
        return $html;
    }

    /**
     * 按 post.format 分派渲染：
     * - markdown：commonmark 管线（GFM 语法转换 + 净化）
     * - html 老文章：仅 HTMLPurifier 净化直出（**不经过 markdown 转换**，
     *   避免 `_x_`/`*x*`/`~~x~~` 等正文混排字符被静默改写）
     * 两条路径均按内容寻址缓存（key 含 format 前缀 + update_time，文章更新即失效）。
     */
    public function renderPost(Post $post): string
    {
        $content = (string) $post->content;
        $key = ($post->format === Post::FORMAT_MARKDOWN ? 'md.' : 'html.')
            . $post->update_time . '.' . sha1($content);
        /** @var string $html */
        $html = $this->cache->getOrSet(
            $key,
            fn (): string => $post->format === Post::FORMAT_MARKDOWN
                ? $this->renderUncached($content)
                : \App\Common\XUtils::htmlPurify($content),
            self::CACHE_TTL,
        );
        return $html;
    }

    /**
     * markdown → HTML 净化管线（HTMLPurifier 白名单，放行代码块 brush class）。
     */
    public function renderUncached(string $markdown): string
    {
        $html = $this->converter->convert($markdown)->getContent();
        return \App\Common\XUtils::htmlPurify($html, [
            'Attr.ClassUseCDATA' => true,
            'Attr.AllowedFrameTargets' => ['_blank'],
            // HTML.Forms 仅为保留 task-list checkbox 而开启；form 标签本身禁止（防正文内嵌钓鱼表单）
            'HTML.Forms' => true,
            'HTML.ForbiddenElements' => ['form'],
        ]);
    }
}
