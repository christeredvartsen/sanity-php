<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

class DefaultListItem
{
    /**
     * @param array{children:array<string>} $item
     */
    public function __invoke($item): string
    {
        return '<li>' . implode('', $item['children']) . '</li>';
    }
}
