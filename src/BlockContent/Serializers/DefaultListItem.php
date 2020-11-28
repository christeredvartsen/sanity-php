<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

class DefaultListItem
{
    public function __invoke($item)
    {
        return '<li>' . implode('', $item['children']) . '</li>';
    }
}
