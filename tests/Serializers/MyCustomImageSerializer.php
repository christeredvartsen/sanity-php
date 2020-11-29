<?php declare(strict_types=1);
namespace Sanity\Serializers;

use Sanity\BlockContent\Serializers\DefaultImage;

class MyCustomImageSerializer extends DefaultImage
{
    public function __invoke($item, $parent, $htmlBuilder): string
    {
        $caption = isset($item['attributes']['caption']) ? $item['attributes']['caption'] : false;
        $url = $this->getImageUrl($item, $htmlBuilder);
        $html = '<figure>';
        $html .= '<img src="' . $url . '" />';
        $html .= $caption ? '<figcaption>' . $htmlBuilder->escape($caption) . '</figcaption>' : '';
        $html .= '</figure>';
        return $html;
    }
}
