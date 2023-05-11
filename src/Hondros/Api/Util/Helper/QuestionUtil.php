<?php
/**
 * Created by PhpStorm.
 * User: Joey Rivera
 * Date: 7/19/2015
 * Time: 1:04 PM
 */

namespace Hondros\Api\Util\Helper;

use Hondros\Api\Model\Entity;

trait QuestionUtil
{
    /**
     * it's job is to find the first right answer and return it. It does not validate that there is a right answer
     * nor checks to see if there is only one.
     *
     * @param array $answers should be a collection of array or the answer entities
     * @return bool|mixed
     */
    function getCorrectAnswerLetter($answers)
    {
        $letters = ['A','B','C','D','E','F','G','H','I', 'J'];
        $currentIndex = 0;

        foreach ($answers as $answer) {
            $correct = $answer instanceof Entity\Answer ? $answer->getCorrect() : $answer['correct'];

            if ((bool) $correct) {
                return $letters[$currentIndex];
            }
            $currentIndex++;
        }

        return false;
    }

    /**
     * @param Entity\Question[] $questions
     * @return array
     */
    function filterActiveOnly($questions)
    {
        $activeQuestions = [];
        foreach ($questions as $question) {
            if ($question->getActive()) {
                $activeQuestions[] = $question;
            }
        }

        return $activeQuestions;
    }
}