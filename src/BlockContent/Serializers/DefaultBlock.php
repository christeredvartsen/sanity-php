<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

class DefaultBlock
{
    /**
     * @param array{style:string,children:string[]} $block
     * @return string
     */
    public function __invoke(array $block): string
    {
        $tag = $block['style'] === 'normal' ? 'p' : $block['style'];
        return '<' . $tag . '>' . implode('', $block['children']) . '</' . $tag . '>';
    }
}
