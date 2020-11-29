<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use PHPUnit\Framework\TestCase;
use Sanity\BlockContent\HtmlBuilder;
use Sanity\Exception\ConfigException;

/**
 * @coversDefaultClass Sanity\BlockContent\Serializers\DefaultImage
 */
class DefaultImageTest extends TestCase
{
    /**
     * @covers ::getImageUrl
     */
    public function testThrowsExceptionOnMissingAsset(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Image does not have required `asset` property');
        (new DefaultImage())(['attributes' => []], null, $this->createConfiguredMock(HtmlBuilder::class, [
            'getProjectId' => 'some-id',
            'getDataset' => 'some-dataset',
            'getImageOptions' => [],
        ]));
    }

    /**
     * @covers ::getImageUrl
     */
    public function testThrowsExceptionOnMissingAssetRef(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid image reference in block, no `_ref` found on `asset`');
        (new DefaultImage())(['attributes' => ['asset' => ['_ref' => null]]], null, $this->createConfiguredMock(HtmlBuilder::class, [
            'getProjectId' => 'some-id',
            'getDataset' => 'some-dataset',
            'getImageOptions' => [],
        ]));
    }

    /**
     * @covers ::getImageUrl
     */
    public function testThrowsExceptionOnInvalidAssetRef(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Invalid `_ref` found on `asset`');
        (new DefaultImage())(['attributes' => ['asset' => ['_ref' => 'invalid-ref']]], null, $this->createConfiguredMock(HtmlBuilder::class, [
            'getProjectId' => 'some-id',
            'getDataset' => 'some-dataset',
            'getImageOptions' => [],
        ]));
    }

    /**
     * @return array<string,array{0:array{getProjectId:?string,getDataset:?string,getImageOptions:array<mixed>}}>
     */
    public function getIncompleteBuilderConfig(): array
    {
        return [
            'missing project id' => [
                [
                    'getProjectId' => null,
                    'getDataset' => 'some-dataset',
                    'getImageOptions' => [],
                ],
            ],
            'missing dataset' => [
                [
                    'getProjectId' => 'some-id',
                    'getDataset' => null,
                    'getImageOptions' => [],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getIncompleteBuilderConfig
     * @covers ::getImageUrl
     * @param array{getProjectId:?string,getDataset:?string,getImageOptions:array<mixed>} $builderConfig
     */
    public function testThrowsExcptionOnIncompleteBlockContentConfig(array $builderConfig): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessageMatches('|^`projectId` and/or `dataset` missing from block content config|');
        (new DefaultImage())([], null, $this->createConfiguredMock(HtmlBuilder::class, $builderConfig));
    }

    /**
     * @return array<
     *   string,
     *   array{
     *     expectedOutput:string,
     *     item:array{
     *       attributes:array{
     *         asset:array{
     *           _ref?:string,
     *           url?:string
     *         }
     *       }
     *     },
     *     builderConfig:array{
     *       getProjectId:string,
     *       getDataset:string,
     *       getImageOptions:array<string,string>
     *     }
     *   }
     *  >
     */
    public function getIO(): array
    {
        return [
            'no image options' => [
                'expectedOutput' => '<figure><img src="https://cdn.sanity.io/images/some-id/some-dataset/id-dimensions.ext" /></figure>',
                'item' => [
                    'attributes' => [
                        'asset' => [
                            '_ref' => 'image-id-dimensions-ext',
                        ],
                    ],
                ],
                'builderConfig' => [
                    'getProjectId' => 'some-id',
                    'getDataset' => 'some-dataset',
                    'getImageOptions' => [],
                ],
            ],
            'with query params' => [
                'expectedOutput' => '<figure><img src="https://cdn.sanity.io/images/some-other-id/some-other-dataset/id123-200x300.jpg?foo=bar" /></figure>',
                'item' => [
                    'attributes' => [
                        'asset' => [
                            '_ref' => 'image-id123-200x300-jpg',
                        ],
                    ],
                ],
                'builderConfig' => [
                    'getProjectId' => 'some-other-id',
                    'getDataset' => 'some-other-dataset',
                    'getImageOptions' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
            'with asset url' => [
                'expectedOutput' => '<figure><img src="https://someurl?foo=bar" /></figure>',
                'item' => [
                    'attributes' => [
                        'asset' => [
                            'url' => 'https://someurl',
                        ],
                    ],
                ],
                'builderConfig' => [
                    'getProjectId' => 'some-other-id',
                    'getDataset' => 'some-other-dataset',
                    'getImageOptions' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getIO
     * @covers ::getImageUrl
     * @param string $expectedOutput
     * @param array{attributes:array{asset:array{_ref?:string,url?:string}}} $item
     * @param array{getProjectId:?string,getDataset:?string,getImageOptions:array<mixed>} $builderConfig
     */
    public function testCanSerializerImages(string $expectedOutput, array $item, array $builderConfig): void
    {
        $this->assertSame(
            $expectedOutput,
            (new DefaultImage())(
                $item,
                null,
                $this->createConfiguredMock(HtmlBuilder::class, $builderConfig),
            ),
        );
    }
}
