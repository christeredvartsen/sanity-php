<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use Sanity\BlockContent\HtmlBuilder;

interface Serializer
{
    /**
     * @param array<string,mixed> $block
     * @param mixed $parent
     * @param HtmlBuilder $builder
     * @return string
     */
    public function __invoke(array $block, $parent, HtmlBuilder $builder): string;
}
