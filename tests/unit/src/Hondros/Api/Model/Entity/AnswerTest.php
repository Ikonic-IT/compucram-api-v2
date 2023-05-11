<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 5/4/17
 * Time: 7:18 PM
 */

namespace Hondros\Unit\Api\Model\Entity;

use Hondros\Api\Model\Entity\Answer;

class AnswerTest extends \PHPUnit\Framework\TestCase
{
    public function testAnswerTextGetsUtf8Encoded()
    {
        $badString = "This is “from word” and not bueno!";

        $question = new Answer();
        $question->setAnswerText($badString);

        $newText = $question->getAnswerText();
        $this->assertNotEquals($badString, $newText);
        $this->assertEquals($newText, 'This is "from word" and not bueno!');
    }
}
