<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

class DefaultBlock
{
    public function __invoke($block)
    {
        $tag = $block['style'] === 'normal' ? 'p' : $block['style'];
        return '<' . $tag . '>' . implode('', $block['children']) . '</' . $tag . '>';
    }
}
