<?php

namespace Hondros\Api\Model\Entity;


/**
 * ReadinessScore
 *
 * View Model
 */
class ReadinessScore
{
    /**
     * @var integer
     *
     */
    private $studentId;

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
     * @var integer Readiness score
     *
     */
    private $readinessScore;
    
    /**
     * @return int $studentId
     */
    public function getStudentId()
    {
        return $this->studentId;
    }

    /**
     * @param number $studentId
     * @return ReadinessScore
     */
    public function setStudentId($studentId)
    {
        $this->studentId = $studentId;
        
        return $this;
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
     * @return ReadinessScore
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
     * @return ReadinessScore
     */
    public function setExternalOrderId($externalOrderId)
    {
        $this->externalOrderId = $externalOrderId;
        
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
     * @return ReadinessScore
     */
    public function setReadinessScore($readinessScore)
    {
        $this->readinessScore = $readinessScore;
        
        return $this;
    }

}
