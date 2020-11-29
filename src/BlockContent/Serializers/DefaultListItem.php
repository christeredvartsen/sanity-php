<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use Sanity\BlockContent\HtmlBuilder;

class DefaultListItem implements Serializer
{
    /**
     * @param array{children:array<string>} $block
     * @param mixed $parent
     * @param HtmlBuilder $builder
     */
    public function __invoke(array $block, $parent, HtmlBuilder $builder): string
    {
        return '<li>' . implode('', $block['children']) . '</li>';
    }
}
