<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use Sanity\BlockContent\HtmlBuilder;

class DefaultBlock implements Serializer
{
    /**
     * @param array{style:string,children:string[]} $block
     * @param mixed $parent
     * @param HtmlBuilder $builder
     * @return string
     */
    public function __invoke(array $block, $parent, HtmlBuilder $builder): string
    {
        $tag = $block['style'] === 'normal' ? 'p' : $block['style'];
        return '<' . $tag . '>' . implode('', $block['children']) . '</' . $tag . '>';
    }
}
