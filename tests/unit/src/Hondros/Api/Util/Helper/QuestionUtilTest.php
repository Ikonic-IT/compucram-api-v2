<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/18/17
 * Time: 1:33 PM
 */

namespace Hondros\Unit\Api\Util\Excel;

use PHPUnit\Framework\TestCase;

class QuestionUtilTest extends TestCase
{
    /**
     * @dataProvider answersForCorrectAnswerLetterMethod
     */
    public function testGetCorrectAnswerLetter($answers, $output)
    {
        $mockTrait = $this->getMockForTrait('Hondros\Api\Util\Helper\QuestionUtil');
        $this->assertEquals($output, $mockTrait->getCorrectAnswerLetter($answers) );
    }

    public function answersForCorrectAnswerLetterMethod()
    {
        return [
            'false' => [[], false],
            'B' => [[
                ['id' => 1, 'correct' => 0],
                ['id' => 2, 'correct' => 1],
                ['id' => 3, 'correct' => 0],
                ['id' => 4, 'correct' => 0],
            ], 'B'],
            'D' => [[
                ['id' => 1, 'correct' => 0],
                ['id' => 2, 'correct' => 0],
                ['id' => 3, 'correct' => 0],
                ['id' => 4, 'correct' => 1],
            ], 'D'],
            'C' => [[
                ['id' => 1, 'correct' => 0],
                ['id' => 2, 'correct' => 0],
                ['id' => 3, 'correct' => 1],
                ['id' => 4, 'correct' => 1],
            ], 'C'],

        ];
    }
}
