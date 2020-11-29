<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use Sanity\BlockContent\HtmlBuilder;

class DefaultSpan
{
    /**
     * @param array{mark?:string|array{_type:string},children:array<string>} $span
     * @param mixed $parent
     * @param HtmlBuilder $htmlBuilder
     * @return string
     */
    public function __invoke(array $span, $parent, HtmlBuilder $htmlBuilder): string
    {
        $head = '';
        $tail = '';
        $mark = isset($span['mark'])
            ? $htmlBuilder->getMarkSerializer($span['mark'])
            : null;

        if ($mark && is_string($mark)) {
            $head .= '<' . $mark . '>';
            $tail .= '</' . $mark . '>';
        } elseif ($mark && is_callable($mark)) {
            return $mark($span['mark'], $span['children']);
        } elseif ($mark && is_array($mark)) {
            $head .= is_callable($mark['head'])
                ? $mark['head']($span['mark'])
                : $mark['head'];

            $tail .= is_callable($mark['tail'])
                ? $mark['tail']($span['mark'])
                : $mark['tail'];
        }

        return $head . implode('', $span['children']) . $tail;
    }
}
