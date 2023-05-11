<?php


namespace Hondros\Unit\Api\Util\Excel;

use PHPUnit\Framework\TestCase;

class StringUtilTest extends TestCase
{
    public function testFormatBytesExpected()
    {
        $mock = $this->getMockForTrait('Hondros\Api\Util\Helper\StringUtil');
        $this->assertEquals('1 MB', $mock->formatBytes(1048576));
    }
}