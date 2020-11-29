<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use PHPUnit\Framework\TestCase;
use Sanity\BlockContent\HtmlBuilder;

/**
 * @coversDefaultClass Sanity\BlockContent\Serializers\DefaultList
 */
class DefaultListTest extends TestCase
{
    /**
     * @return array<string,array{expectedOutput:string,list:array{itemStyle?:string,children:array<string>}}>
     */
    public function getIO(): array
    {
        return [
            'default list style' => [
                'expectedOutput' => '<ul><li>foo</li><li>bar</li></ul>',
                'list' => [
                    'itemStyle' => 'default',
                    'children' => ['<li>foo</li>', '<li>bar</li>'],
                ],
            ],
            'numbered list' => [
                'expectedOutput' => '<ol><li>foo</li><li>bar</li></ol>',
                'list' => [
                    'itemStyle' => 'number',
                    'children' => ['<li>foo</li>', '<li>bar</li>'],
                ],
            ],
            'custom list' => [
                'expectedOutput' => '<ul><li>foo</li></ul>',
                'list' => [
                    'itemStyle' => 'wut',
                    'children' => ['<li>foo</li>'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getIO
     * @covers ::__invoke
     * @param string $expectedOutput
     * @param array{itemStyle?:string,children:array<string>} $list
     */
    public function testCanSerializeDefaultLists(string $expectedOutput, array $list): void
    {
        $this->assertSame($expectedOutput, (new DefaultList())($list, null, $this->createMock(HtmlBuilder::class)));
    }
}
