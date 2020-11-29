<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use Sanity\BlockContent\HtmlBuilder;

class DefaultList implements Serializer
{
    /**
     * @param array{itemStyle?:string,children:array<string>} $block
     * @param mixed $parent
     * @param HtmlBuilder $builder
     */
    public function __invoke(array $block, $parent, HtmlBuilder $builder): string
    {
        $style = isset($block['itemStyle']) ? $block['itemStyle'] : 'default';
        $tagName = $style === 'number' ? 'ol' : 'ul';
        return '<' . $tagName . '>' . implode('', $block['children']) . '</' . $tagName . '>';
    }
}
