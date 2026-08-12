<?php

declare(strict_types=1);

namespace App\Post;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * 代码块渲染为 SyntaxHighlighter 兼容输出：
 * ```php → <pre class="brush:php">…</pre>（与老站静态资源 sh/scripts 对接）。
 */
final class SyntaxHighlighterCodeRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if ($node instanceof FencedCode) {
            $language = $node->getInfoWords()[0] ?? 'plain';
            $content = $node->getLiteral();
        } elseif ($node instanceof IndentedCode) {
            $language = 'plain';
            $content = $node->getLiteral();
        } else {
            return '';
        }

        if ($language === '') {
            $language = 'plain';
        }

        return '<pre class="brush:' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8')
            . '">' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '</pre>';
    }
}
