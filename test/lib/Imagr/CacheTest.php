<?php

use Imagr\Cache;

/**
 *
 * @author  Kevin Bradwick <kbradwick@gmail.com>
 * @package
 * @license
 */
class CacheTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionThrownWhenInvalidCacheDirectorySpecified()
    {
        $cache = new Cache(__FILE__);
    }

    /**
     * Test unknown keys are null
     */
    public function testGettingUnknownCacheReturnsNull()
    {
        $cache = new Cache(realpath(__DIR__ . '/../../../src/cache'));
        $this->assertInternalType('null', $cache->get('unknown'));
    }

    /**
     * Set an item to the cache
     */
    public function testSetNewItemToCache()
    {
        $cache = new Cache(realpath(__DIR__ . '/../../../src/cache'));
        $cache->set('foo', 'bar');

        $this->assertTrue($cache->has('foo'));
        $this->assertEquals('bar', $cache->get('foo'));

        $cache->flush();
    }
}
