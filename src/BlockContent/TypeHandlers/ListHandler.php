<?php declare(strict_types=1);
namespace Sanity\BlockContent\TypeHandlers;

use Sanity\BlockContent\TreeBuilder;

class ListHandler implements Handler
{
    /**
     * @param array $block
     * @param TreeBuilder $builder
     * @return array
     */
    public function __invoke(array $block, TreeBuilder $builder): array
    {
        return [
            'type' => 'list',
            'itemStyle' => isset($block[0]['listItem']) ? $block[0]['listItem'] : '',
            'items' => array_map(
                fn ($item) => $builder->typeHandlers['block']($item, $builder),
                $block,
            ),
        ];
    }
}
