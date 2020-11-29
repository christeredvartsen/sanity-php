<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use Sanity\BlockContent\HtmlBuilder;
use Sanity\Exception\ConfigException;

class DefaultImage
{
    private string $baseUri = 'https://cdn.sanity.io/';

    /**
     * @param array{attributes:array{asset?:array{url?:string,_ref?:string}}} $item
     * @param mixed $parent
     * @param HtmlBuilder $htmlBuilder
     * @return string
     */
    public function __invoke(array $item, $parent, HtmlBuilder $htmlBuilder): string
    {
        $url = $this->getImageUrl($item, $htmlBuilder);
        return '<figure><img src="' . $url . '" /></figure>';
    }

    /**
     * @param array{attributes:array{asset?:array{url?:string,_ref?:string}}} $item
     * @param HtmlBuilder $htmlBuilder
     * @throws ConfigException
     * @return string
     */
    protected function getImageUrl(array $item, HtmlBuilder $htmlBuilder): string
    {
        $projectId = $htmlBuilder->getProjectId();
        $dataset = $htmlBuilder->getDataset();
        $imageOptions = $htmlBuilder->getImageOptions();

        if (null === $projectId || null === $dataset) {
            throw new ConfigException(
                '`projectId` and/or `dataset` missing from block content config, see ' .
                'https://github.com/sanity-io/sanity-php#rendering-block-content',
            );
        }

        $node = $item['attributes'];
        $asset = $node['asset'] ?? null;

        if (!is_array($asset)) {
            throw new ConfigException('Image does not have required `asset` property');
        }

        $qs = http_build_query($imageOptions);
        if (!empty($qs)) {
            $qs = '?' . $qs;
        }

        if (isset($asset['url'])) {
            return $asset['url'] . $qs;
        }

        $ref = $asset['_ref'] ?? null;
        if (!is_string($ref)) {
            throw new ConfigException('Invalid image reference in block, no `_ref` found on `asset`');
        }

        $parts = explode('-', $ref);
        if (4 !== count($parts)) {
            throw new ConfigException('Invalid `_ref` found on `asset`');
        }

        $url = $this->baseUri
            . $parts[0] . 's/' // Asset type, pluralized
            . $projectId . '/'
            . $dataset  . '/'
            . $parts[1] . '-'  // Asset ID
            . $parts[2] . '.'  // Dimensions
            . $parts[3]        // File extension
            . $qs;             // Query string

        return $url;
    }
}
