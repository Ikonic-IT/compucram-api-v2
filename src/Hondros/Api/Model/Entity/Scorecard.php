<?php

namespace Hondros\Api\Model\Entity;


/**
 * Scorecard
 *
 * View Model
 */
class Scorecard
{

    /**
     * @var integer
     *
     */
    private $enrollmentId;
    
    /**
     * @var string External id of the enrollment
     *
     */
    private $externalOrderId;

    /**
     * @var integer The exam score average is the sum of all completed simulated exam scores divided by the number of completed simulated exams
     *
     */
    private $examScoreAverage;
    
    /**
     * @var integer The question bank percent correct is the number of correctly answered distinct practice questions for all exam categories divided by the number of all practice questions for all exam categories 
     *
     */
    private $questionBankPercentCorrect;

    /**
     * @var float The hours studied is the total time tracked by CompuCram in all the different areas.
     *
     */
    private $hoursStudied;

    /**
     * @var integer Complex score calculated using different sections of the exam
     *
     */
    private $readinessScore;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection List of exam category scores
     *
     */
    private $examCategoryScores;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection List of simulated exam scores
     *
     */
    private $simulatedExamScores;


    /**
     * Constructor
     **/
    public function __construct()
    {
        $this->enrollmentId = null;
        $this->externalOrderId = "";
        $this->examScoreAverage = null;
        $this->questionBankPercentCorrect = 0;
        $this->hoursStudied = 0;
        $this->readinessScore = 0;
        $this->examCategoryScores = new \Doctrine\Common\Collections\ArrayCollection();
        $this->simulatedExamScores = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return int $enrollmentId
     */
    public function getEnrollmentId()
    {
        return $this->enrollmentId;
    }

    /**
     * @param number $enrollmentId
     * @return Scorecard
     */
    public function setEnrollmentId($enrollmentId)
    {
        $this->enrollmentId = $enrollmentId;
        
        return $this;
    }

    /**
     * @return int $externalOrderId
     */
    public function getExternalOrderId()
    {
        return $this->externalOrderId;
    }

    /**
     * @param number $externalOrderId
     * @return Scorecard
     */
    public function setExternalOrderId($externalOrderId)
    {
        $this->externalOrderId = $externalOrderId;
        
        return $this;
    }

    /**
     * @return int $examScoreAverage
     */
    public function getExamScoreAverage()
    {
        return $this->examScoreAverage;
    }

    /**
     * @param number $examScoreAverage
     * @return Scorecard
     */
    public function setExamScoreAverage($examScoreAverage)
    {
        $this->examScoreAverage = $examScoreAverage;
        
        return $this;
    }

    /**
     * @return int $questionBankPercentCorrect
     */
    public function getQuestionBankPercentCorrect()
    {
        return $this->questionBankPercentCorrect;
    }

    /**
     * @param number $questionBankPercentCorrect
     * @return Scorecard
     */
    public function setQuestionBankPercentCorrect($questionBankPercentCorrect)
    {
        $this->questionBankPercentCorrect = $questionBankPercentCorrect;
        
        return $this;
    }

    /**
     * @return int $hoursStudied
     */
    public function getHoursStudied()
    {
        return $this->hoursStudied;
    }

    /**
     * @param number $hoursStudied
     * @return Scorecard
     */
    public function setHoursStudied($hoursStudied)
    {
        $this->hoursStudied = $hoursStudied;
        
        return $this;
    }

    /**
     * @return int $readinessScore
     */
    public function getReadinessScore()
    {
        return $this->readinessScore;
    }

    /**
     * @param number $readinessScore
     * @return Scorecard
     */
    public function setReadinessScore($readinessScore)
    {
        $this->readinessScore = $readinessScore;
        
        return $this;
    }

    /**
     * @param string $examCategory
     * @param int $score
     * @return Scorecard
     */
    public function addExamCategoryScore($examCategory, $score)
    {
        $examCategoryScore = new \Hondros\Api\Model\Entity\ExamCategoryScore();
        $examCategoryScore->setExamCategory($examCategory);
        $examCategoryScore->setScore($score);
        $this->examCategoryScores->add($examCategoryScore);
        return $this;
    }
    
    /**
     * @return \Doctrine\Common\Collections\Collection $examCategoryScores
     */
    public function getExamCategoryScores()
    {
        return $this->examCategoryScores;
    }

    /**
     * @param \Hondros\Api\Model\Entity\AssessmentAttempt $assessmentAttempt
     * @return Scorecard
     */
    public function addSimulatedExamScore($assessmentAttempt)
    {
        $simulatedExamScore = new \Hondros\Api\Model\Entity\SimulatedExamScore();
        $simulatedExamScore->setCompleted($assessmentAttempt->getCompleted());
        $simulatedExamScore->setScore($assessmentAttempt->getScore());
        $this->simulatedExamScores->add($simulatedExamScore);
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection $simulatedExamScore
     */
    public function getSimulatedExamScores()
    {
        return $this->simulatedExamScores;
    }
}
