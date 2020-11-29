<?php declare(strict_types=1);
namespace Sanity\BlockContent\TypeHandlers;

use Sanity\BlockContent\TreeBuilder;

class BlockHandler implements Handler
{
    /**
     * @param array $block
     * @param TreeBuilder $builder
     * @return array
     */
    public function __invoke(array $block, TreeBuilder $builder): array
    {
        return [
            'type' => 'block',
            'style' => isset($block['style']) ? $block['style'] : 'normal',
            'content' => isset($block['children']) ? $builder->parseSpans($block['children'], $block) : [],
        ];
    }
}
