<?php

declare(strict_types=1);

namespace App\Post\FencedDiv;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * 围栏 div → <div …> 包裹容器（输出结构对齐 Pandoc Writers/HTML.hs 的 div writer）。
 *
 * 属性数组由 FencedDivParser 在解析期写入 data['attributes']；
 * 输出后再经 HTMLPurifier 白名单裁定（class/id/title/style 的最终形态）。
 */
final class FencedDivRenderer implements NodeRendererInterface
{
    /**
     * @param FencedDiv $node
     *
     * {@inheritDoc}
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        FencedDiv::assertInstanceOf($node);

        // data['attributes'] 由 FencedDivParser 写入；此处运行时收窄为 HtmlElement 可接受的标量属性集
        $raw    = $node->data->get('attributes');
        $attrs  = [];
        if (\is_array($raw)) {
            foreach ($raw as $name => $value) {
                if (\is_string($name) && (\is_string($value) || \is_bool($value))) {
                    $attrs[$name] = $value;
                }
            }
        }

        $filling        = $childRenderer->renderNodes($node->children());
        $innerSeparator = $childRenderer->getInnerSeparator();
        if ($filling === '') {
            return new HtmlElement('div', $attrs, $innerSeparator);
        }

        return new HtmlElement(
            'div',
            $attrs,
            $innerSeparator . $filling . $innerSeparator,
        );
    }
}
