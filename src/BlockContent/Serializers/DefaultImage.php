<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use Sanity\BlockContent\HtmlBuilder;
use Sanity\Exception\ConfigException;

class DefaultImage implements Serializer
{
    private string $baseUri = 'https://cdn.sanity.io/';

    /**
     * @param array{attributes:array{asset?:array{url?:string,_ref?:string}}} $block
     * @param mixed $parent
     * @param HtmlBuilder $builder
     * @return string
     */
    public function __invoke(array $block, $parent, HtmlBuilder $builder): string
    {
        $url = $this->getImageUrl($block, $builder);
        return '<figure><img src="' . $url . '" /></figure>';
    }

    /**
     * @param array{attributes:array{asset?:array{url?:string,_ref?:string}}} $block
     * @param HtmlBuilder $builder
     * @throws ConfigException
     * @return string
     */
    protected function getImageUrl(array $block, HtmlBuilder $builder): string
    {
        $projectId = $builder->getProjectId();
        $dataset = $builder->getDataset();
        $imageOptions = $builder->getImageOptions();

        if (null === $projectId || null === $dataset) {
            throw new ConfigException(
                '`projectId` and/or `dataset` missing from block content config, see ' .
                'https://github.com/sanity-io/sanity-php#rendering-block-content',
            );
        }

        $node = $block['attributes'];
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
