<?php

declare(strict_types=1);

namespace App\Post;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * 代码块渲染为 highlight.js 兼容输出：
 * ```php → <pre><code class="language-php">…</code></pre>。
 */
final class SyntaxHighlighterCodeRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if ($node instanceof FencedCode) {
            $language = $node->getInfoWords()[0] ?? '';
            $content = $node->getLiteral();
        } elseif ($node instanceof IndentedCode) {
            $language = '';
            $content = $node->getLiteral();
        } else {
            return '';
        }

        $langAttr = $language !== ''
            ? ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"'
            : '';

        return '<pre><code' . $langAttr . '>'
            . htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
            . '</code></pre>';
    }
}
