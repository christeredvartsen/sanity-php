<?php declare(strict_types=1);
namespace Sanity\BlockContent\Serializers;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Sanity\BlockContent\Serializers\DefaultListItem
 */
class DefaultListItemTest extends TestCase
{
    /**
     * @covers ::__invoke
     */
    public function testCanSerializeDefaultListItems(): void
    {
        $this->assertSame('<li>foobar</li>', (new DefaultListItem())(['children' => ['foo', 'bar']]));
    }
}
