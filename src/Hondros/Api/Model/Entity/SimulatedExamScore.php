<?php

namespace Hondros\Api\Model\Entity;


/**
 * SimulatedExamScore
 *
 * View Model
 */
class SimulatedExamScore
{

    /**
     * @var \DateTime
     *
     */
    private $completed;

    /**
     * @var integer Score of exam
     *
     */
    private $score;
    
    /**
     * Set completed
     *
     * @param \DateTime $completed
     * @return SimulatedExamScore
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
        return $this;
    }

    /**
     * Get completed date
     *
     * @return \DateTime 
     */
    public function getCompletedDateUtc()
    {
        return $this->completed;
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
     * @return SimulatedExamScore
     */
    public function setScore($score)
    {
        $this->score = $score;
        
        return $this;
    }

}
