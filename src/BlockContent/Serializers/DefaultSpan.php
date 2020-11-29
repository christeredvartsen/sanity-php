<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use Sanity\BlockContent\HtmlBuilder;

class DefaultSpan implements Serializer
{
    /**
     * @param array{mark?:string|array{_type:string},children:array<string>} $block
     * @param mixed $parent
     * @param HtmlBuilder $builder
     * @return string
     */
    public function __invoke(array $block, $parent, HtmlBuilder $builder): string
    {
        $head = '';
        $tail = '';
        $mark = isset($block['mark'])
            ? $builder->getMarkSerializer($block['mark'])
            : null;

        if ($mark && is_string($mark)) {
            $head .= '<' . $mark . '>';
            $tail .= '</' . $mark . '>';
        } elseif ($mark && is_callable($mark)) {
            return $mark($block['mark'], $block['children']);
        } elseif ($mark && is_array($mark)) {
            $head .= is_callable($mark['head'])
                ? $mark['head']($block['mark'])
                : $mark['head'];

            $tail .= is_callable($mark['tail'])
                ? $mark['tail']($block['mark'])
                : $mark['tail'];
        }

        return $head . implode('', $block['children']) . $tail;
    }
}
