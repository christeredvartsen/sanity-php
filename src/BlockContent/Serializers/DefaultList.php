<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

class DefaultList
{
    /**
     * @param array{itemStyle?:string,children:array<string>} $list
     */
    public function __invoke(array $list): string
    {
        $style = isset($list['itemStyle']) ? $list['itemStyle'] : 'default';
        $tagName = $style === 'number' ? 'ol' : 'ul';
        return '<' . $tagName . '>' . implode('', $list['children']) . '</' . $tagName . '>';
    }
}
