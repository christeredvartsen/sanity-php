<?php
namespace Sanity;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base class for Sanity test cases, provides utility methods and shared logic.
 */
class TestCase extends PHPUnitTestCase
{
    public function loadFixture($fixtureName)
    {
        $content = file_get_contents(__DIR__ . '/fixtures/' . $fixtureName);
        return json_decode($content, true);
    }
}
