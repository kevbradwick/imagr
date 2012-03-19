<?php

use Imagr\Request;

/**
 * Created by JetBrains PhpStorm.
 * User: kevin
 * Date: 19/03/12
 * Time: 09:56
 * To change this template use File | Settings | File Templates.
 */
class RequestTest extends PHPUnit_Framework_TestCase
{
    public function testGetParameters()
    {
        $params = array('foo' => 'bar', 'name' => 'Imagr', 'src' => 'http://www.google.com');
        $params2 = array('foo' => 'foo');
        $request = new Request(array($params, $params2));

        $this->assertEquals('foo', $request->get('foo'));
        $this->assertEquals('foobar', $request->get('unknown', 'foobar'));
    }
}
