<?php declare(strict_types=1);
namespace Sanity\BlockContent\TypeHandlers;

use Sanity\BlockContent\TreeBuilder;

interface Handler
{
    /**
     * @param array $block
     * @param TreeBuilder $builder
     * @return array
     */
    public function __invoke(array $block, TreeBuilder $builder): array;
}
