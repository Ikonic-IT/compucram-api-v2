<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 2/18/17
 * Time: 11:03 PM
 */

namespace Hondros\Unit\Api\Client\MailChimp;

use Hondros\Api\Client\MailChimp\Subscriber;
use PHPUnit\Framework\TestCase;

class SubscriberTest extends TestCase
{
    public function testGetMergeFieldNoValues()
    {
        $subscriber = new Subscriber();
        $array = $subscriber->getMergeFields();
        $this->assertEmpty($array);
    }

    public function testGetMergeFieldAllEmptyValues()
    {
        $subscriber = new Subscriber();
        $array = $subscriber->getMergeFields(true);
        $this->assertNotEmpty($array);
        foreach ($array as $key => $value) {
            $this->assertNotEmpty($key);
            $this->assertNull($value);
        }
    }

    public function testGetMergeFieldOneValueMatches()
    {
        $subscriber = new Subscriber();
        $subscriber->setIndustryName('panda');
        $array = $subscriber->getMergeFields();
        $this->assertNotEmpty($array);
        $this->assertCount(1, $array);
        $this->assertArrayHasKey('INDUSTRYNA', $array);
    }
}
