<?php declare(strict_types=1);
namespace Sanity\BlockContent\TypeHandlers;

use Sanity\BlockContent\TreeBuilder;

class DefaultHandler implements Handler
{
    /**
     * @param array $block
     * @param TreeBuilder $builder
     * @return array
     */
    public function __invoke(array $block, TreeBuilder $builder): array
    {
        $type = $block['_type'];
        $attributes = $block;
        unset($attributes['_type']);

        return [
            'type' => $type,
            'attributes' => $attributes,
        ];
    }
}
