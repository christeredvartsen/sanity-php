<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use PHPUnit\Framework\TestCase;
use Sanity\BlockContent\HtmlBuilder;

/**
 * @coversDefaultClass Sanity\BlockContent\Serializers\DefaultBlock
 */
class DefaultBlockTest extends TestCase
{
    /**
     * @return array<string,array{0:array{style:string,children:string[]},1:string}>
     */
    public function getIO(): array
    {
        return [
            'normal style' => [
                [
                    'style' => 'normal',
                    'children' => ['foo', 'bar'],
                ],
                '<p>foobar</p>',
            ],
            'custom style' => [
                [
                    'style' => 'h1',
                    'children' => ['bar', 'foo'],
                ],
                '<h1>barfoo</h1>',
            ],
        ];
    }

    /**
     * @dataProvider getIO
     * @covers ::__invoke
     * @param array{style:string,children:string[]} $block
     * @param string $expectedOutput
     */
    public function testCanSerializeDefaultBlock(array $block, string $expectedOutput): void
    {
        $serializer = new DefaultBlock();
        $this->assertSame(
            $expectedOutput,
            $serializer($block, null, $this->createMock(HtmlBuilder::class)),
        );
    }
}
