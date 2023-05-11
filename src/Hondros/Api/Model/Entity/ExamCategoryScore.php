<?php

namespace Hondros\Api\Model\Entity;


/**
 * ExamCategoryScore
 *
 * View Model
 */
class ExamCategoryScore
{

    /**
     * @var string name of exam category / module
     *
     */
    private $examCategory;
    
    /**
     * @var integer Score of exam category / module
     *
     */
    private $score;
    
    /**
     * @return int $examCategory
     */
    public function getExamCategory()
    {
        return $this->examCategory;
    }

    /**
     * @param string $examCategory
     * @return ExamCategoryScore
     */
    public function setExamCategory($examCategory)
    {
        $this->examCategory = $examCategory;
        
        return $this;
    }

    /**
     * @return int $score
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param string $score
     * @return ExamCategoryScore
     */
    public function setScore($score)
    {
        $this->score = $score;
        
        return $this;
    }

}
