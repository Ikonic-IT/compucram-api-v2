<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 5/4/17
 * Time: 7:18 PM
 */

namespace Hondros\Unit\Api\Model\Entity;

use Hondros\Api\Model\Entity\Question;

class QuestionTest extends \PHPUnit\Framework\TestCase
{
    public function testQuestionTextGetsUtf8Encoded()
    {
        $badString = "This is “from word” and not bueno!";

        $question = new Question();
        $question->setQuestionText($badString);

        $newText = $question->getQuestionText();
        $this->assertNotEquals($badString, $newText);
        $this->assertEquals($newText, 'This is "from word" and not bueno!');
    }

    public function testFeedbackGetsUtf8Encoded()
    {
        $badString = "This is “from word” and not bueno!";

        $question = new Question();
        $question->setFeedback($badString);

        $newText = $question->getFeedback();
        $this->assertNotEquals($badString, $newText);
        $this->assertEquals($newText, 'This is "from word" and not bueno!');
    }

    public function testTechniqueGetsUtf8Encoded()
    {
        $badString = "This is “from word” and not bueno!";

        $question = new Question();
        $question->setTechniques($badString);

        $newText = $question->getTechniques();
        $this->assertNotEquals($badString, $newText);
        $this->assertEquals($newText, 'This is "from word" and not bueno!');
    }

}
