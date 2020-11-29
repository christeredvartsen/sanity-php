<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use PHPUnit\Framework\TestCase;
use Sanity\BlockContent\HtmlBuilder;

/**
 * @coversDefaultClass Sanity\BlockContent\Serializers\DefaultSpan
 */
class DefaultSpanTest extends TestCase
{
    /**
     * @return array<
     *   string,
     *   array{
     *     expectedOutput:string,
     *     span:array{
     *       mark?:string|array{_type:string},
     *       children:array<string>
     *     },
     *     markSerializer:string|callable|array{head:string|callable,tail:string|callable}|null
     *   }
     * >
     */
    public function getIO(): array
    {
        return [
            'no mark' => [
                'expectedOutput' => 'foobar',
                'span' => ['children' => ['foo', 'bar']],
                'markSerializer' => null,
            ],

            'string mark' => [
                'expectedOutput' => '<span>foobar</span>',
                'span' => ['mark' => 'span', 'children' => ['foo', 'bar']],
                'markSerializer' => 'span',
            ],
            'callable mark' => [
                'expectedOutput' => '<span>foobar</span>',
                'span' => ['mark' => 'span', 'children' => ['foo', 'bar']],
                'markSerializer' => function (string $mark, array $children): string {
                    if ('span' !== $mark) {
                        $this->fail('Invalid `mark` passed to callback');
                    } elseif (['foo', 'bar'] !== $children) {
                        $this->fail('Invalid `children` passed to callback');
                    }

                    return '<span>foobar</span>';
                },
            ],
            'array mark with static head and tail' => [
                'expectedOutput' => '<h1>foobar</h1>',
                'span' => ['mark' => 'h1', 'children' => ['foo', 'bar']],
                'markSerializer' => [
                    'head' => '<h1>',
                    'tail' => '</h1>',
                ],
            ],
            'array mark with callable head and tail' => [
                'expectedOutput' => '<span>foobar</span>',
                'span' => ['mark' => 'span', 'children' => ['foo', 'bar']],
                'markSerializer' => [
                    'head' => function (string $mark): string {
                        if ('span' !== $mark) {
                            $this->fail('Invalid `mark` passed to callback');
                        }

                        return '<span>';
                    },
                    'tail' => function (string $mark): string {
                        if ('span' !== $mark) {
                            $this->fail('Invalid `mark` passed to callback');
                        }

                        return '</span>';
                    },
                ],
            ],
        ];
    }
    /**
     * @dataProvider getIO
     * @covers ::__invoke
     * @param string $expectedOutput
     * @param array{mark?:string|array{_type:string},children:array<string>} $span
     * @param string|callable|array{head:string|callable,tail:string|callable}|null $markSerializer
     */
    public function testCanSerializeDefaultSpan(string $expectedOutput, array $span, $markSerializer = null): void
    {
        $builder = $this->createMock(HtmlBuilder::class);

        if (isset($span['mark']) && null !== $markSerializer) {
            $builder
                ->expects($this->once())
                ->method('getMarkSerializer')
                ->with($span['mark'])
                ->willReturn($markSerializer);
        } else {
            $builder
                ->expects($this->never())
                ->method('getMarkSerializer');
        }

        $this->assertSame($expectedOutput, (new DefaultSpan())($span, null, $builder));
    }
}
