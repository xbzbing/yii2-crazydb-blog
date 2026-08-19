<?php

declare(strict_types=1);

namespace App\Post;

use League\HTMLToMarkdown\HtmlConverter;

/**
 * 旧版 HTML 文章 → Markdown 转换服务。
 *
 * 用途：后台编辑旧文章（UEditor 时代的 format=html）时，把正文/摘要
 * 转换为 Markdown，使 Vditor 编辑器可直接加载；保存时以 format=markdown
 * 落库，即"编辑即转换"。
 *
 * 旧格式特征（UEditor）需要预处理，否则转换结果不佳：
 * - <span style="..."> 包裹正文：style 属性会残留 → 剥离 style 属性
 * - <pre class="brush:php;toolbar:false">：SyntaxHighlighter 风格代码块，
 *   无 <code> 子元素、语言在 class 里 → 转为 <pre><code class="language-php">
 * - <p style="...">、<img style/width/...>：由转换器 strip 样式属性
 */
final class HtmlToMarkdownService
{
    /** UEditor brush 语言 → highlight.js 语言名 */
    private const BRUSH_MAP = [
        'plain' => 'plaintext',
        'text' => 'plaintext',
        'bash' => 'bash',
        'shell' => 'bash',
        'sh' => 'bash',
        'php' => 'php',
        'python' => 'python',
        'py' => 'python',
        'java' => 'java',
        'c' => 'c',
        'cpp' => 'cpp',
        'csharp' => 'csharp',
        'js' => 'javascript',
        'javascript' => 'javascript',
        'sql' => 'sql',
        'xml' => 'xml',
        'html' => 'xml',
        'css' => 'css',
        'json' => 'json',
        'ruby' => 'ruby',
        'go' => 'go',
        'less' => 'less',
        'scss' => 'scss',
        'typescript' => 'typescript',
        'ts' => 'typescript',
    ];

    private HtmlConverter $converter;

    public function __construct()
    {
        $this->converter = new HtmlConverter([
            // 保留 code/pre 原样（后续转 fenced code），图片转 markdown 语法
            'strip_tags' => false,
            'preserve_comments' => false,
            'remove_nodes' => 'head script style',
        ]);
        $this->converter->getEnvironment()->addConverter(
            new \League\HTMLToMarkdown\Converter\PreformattedConverter(),
        );
    }

    /**
     * 将 HTML 正文转换为 Markdown。
     */
    public function convert(string $html): string
    {
        $html = $this->preprocess($html);
        $markdown = $this->converter->convert($html);
        // 转换器可能产出连续空行（样式块剥离后），压缩为最多一个空行
        return trim((string) preg_replace('/\n{3,}/', "\n\n", $markdown)) . "\n";
    }

    /**
     * 预处理 UEditor 旧格式：
     * 1. 行内标签处理（DOM）：span/font 解包、剥离 style/class 等展示属性
     * 2. brush 代码块 → <pre><code class="language-xxx">（转换器才能识别语言；
     *    必须在 DOM 处理之后，否则 language-* class 会被步骤 1 剥离）
     */
    private function preprocess(string $html): string
    {
        // 1. 行内标签处理（DOM 方式，保证开闭标签成对）
        $html = $this->unwrapInlineTags($html);

        // 2. brush 代码块
        $html = (string) preg_replace_callback(
            '/<pre\b[^>]*class=["\'][^"\']*brush:\s*([\w-]+)[^"\']*["\'][^>]*>(.*?)<\/pre>/is',
            function (array $m): string {
                $lang = self::BRUSH_MAP[strtolower($m[1])] ?? strtolower($m[1]);
                // 解 HTML 实体（brush 块内容经 UEditor 转义）
                $code = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return '<pre><code class="language-' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($code, ENT_QUOTES, 'UTF-8')
                    . '</code></pre>';
            },
            $html,
        );

        return $html;
    }

    /**
     * 用 DOMDocument 处理行内标签：span/font 解包，其余标签剥离展示属性。
     * 使用 DOM 而非正则，保证开闭标签成对、嵌套正确。
     */
    private function unwrapInlineTags(string $html): string
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        // 不指定 LIBXML_HTML_NOIMPLIED：让 libxml 自动包裹 html/body，
        // 之后从 body 提取内容（NOIMPLIED 下无 body 节点，序列化会丢结构）
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->stripDisplayAttrs($dom);

        // 解包 span/font：把子节点提升到父级位置，删除标签本身
        $xp = new \DOMXPath($dom);
        foreach ($xp->query('//span | //font') as $node) {
            /** @var \DOMElement $node */
            $parent = $node->parentNode;
            if ($parent === null) {
                continue; // 防御：文档根等极端情况下无父节点
            }
            while ($node->firstChild !== null) {
                $parent->insertBefore($node->firstChild, $node);
            }
            $parent->removeChild($node);
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $inner = '';
        if ($body !== null) {
            foreach ($body->childNodes as $child) {
                $inner .= $dom->saveHTML($child);
            }
        }
        // 去掉可能残留的 XML 编码声明
        return (string) preg_replace('/^<\?xml[^>]*>/', '', $inner);
    }

    /**
     * 递归剥离元素上的展示属性（style/width/height/border 等）。
     * 注意：pre/code 的 class 必须保留（brush: 语言标记、language-* 都依赖它）。
     */
    private function stripDisplayAttrs(\DOMDocument $dom): void
    {
        $xp = new \DOMXPath($dom);
        $stripAttrs = ['style', 'width', 'height', 'border', 'vspace', 'hspace', 'align', 'cellpadding', 'cellspacing'];
        foreach ($xp->query('//*') as $node) {
            /** @var \DOMElement $node */
            $tag = strtolower($node->nodeName);
            foreach ($stripAttrs as $attr) {
                if ($node->hasAttribute($attr)) {
                    $node->removeAttribute($attr);
                }
            }
            // 除 pre/code 外的标签剥 class（pre 的 brush: 标记、code 的 language-* 需保留）
            if ($tag !== 'pre' && $tag !== 'code' && $node->hasAttribute('class')) {
                $node->removeAttribute('class');
            }
        }
    }
}
