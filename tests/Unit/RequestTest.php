<?php

namespace BlueBillywig\Tests\Unit;

use BlueBillywig\Request;

class RequestTest extends \Codeception\Test\Unit
{
    public function testGetQueryParams()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/some/path?param1=value1&param2=value2&param3=value3");
        $expected = [
            'param1' => 'value1',
            'param2' => 'value2',
            'param3' => 'value3'
        ];
        $this->assertEmpty(array_diff_assoc($expected, $request->getQueryParams()));
    }

    public function testGetQueryParam()
    {
        $request = new Request("GET", "https://www.bluebillywig.com/some/path?param1=value1&param2=value2&param3=value3");
        $this->assertEquals('value2', $request->getQueryParam('param2'));
    }
}
