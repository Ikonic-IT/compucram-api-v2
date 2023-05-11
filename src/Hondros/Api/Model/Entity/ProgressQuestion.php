<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProgressQuestion
 *
 * @ORM\Table(name="progress_question", indexes={@ORM\Index(name="fk_progress_question_progress1_idx", columns={"progress_id"}), @ORM\Index(name="fk_progress_question_question1_idx", columns={"question_id"})})
 * @ORM\Entity
 */
class ProgressQuestion
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="progress_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $progressId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="viewed", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $viewed = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="answered", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $answered = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="correct", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $correct = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="bookmarked", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $bookmarked = 0;

    /**
     * @var \Hondros\Api\Model\Entity\Progress
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Progress", inversedBy="questions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="progress_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $progress;

    /**
     * @var \Hondros\Api\Model\Entity\Question
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Question")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="question_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $question;


    /**
     * @return the $progressId
     */
    public function getProgressId()
    {
        return $this->progressId;
    }

	/**
     * @param number $progressId
     */
    public function setProgressId($progressId)
    {
        $this->progressId = $progressId;
        
        return $this;
    }

	/**
     * @return the $questionId
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

	/**
     * @param number $questionId
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;
        
        return $this;
    }

	/**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set viewed
     *
     * @param integer $viewed
     * @return ProgressQuestion
     */
    public function setViewed($viewed)
    {
        $this->viewed = $viewed;

        return $this;
    }

    /**
     * Get viewed
     *
     * @return integer 
     */
    public function getViewed()
    {
        return $this->viewed;
    }

    /**
     * Set answered
     *
     * @param boolean $answered
     * @return ProgressQuestion
     */
    public function setAnswered($answered)
    {
        $this->answered = $answered;

        return $this;
    }

    /**
     * Get answered
     *
     * @return boolean 
     */
    public function getAnswered()
    {
        return $this->answered;
    }

    /**
     * Set correct
     *
     * @param boolean $correct
     * @return ProgressQuestion
     */
    public function setCorrect($correct)
    {
        $this->correct = $correct;

        return $this;
    }

    /**
     * Get correct
     *
     * @return boolean 
     */
    public function getCorrect()
    {
        return $this->correct;
    }

    /**
     * Set bookmarked
     *
     * @param boolean $bookmarked
     * @return ProgressQuestion
     */
    public function setBookmarked($bookmarked)
    {
        $this->bookmarked = $bookmarked;

        return $this;
    }

    /**
     * Get bookmarked
     *
     * @return boolean 
     */
    public function getBookmarked()
    {
        return $this->bookmarked;
    }

    /**
     * Set progress
     *
     * @param \Hondros\Api\Model\Entity\Progress $progress
     * @return ProgressQuestion
     */
    public function setProgress(\Hondros\Api\Model\Entity\Progress $progress = null)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return \Hondros\Api\Model\Entity\Progress 
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set question
     *
     * @param \Hondros\Api\Model\Entity\Question $question
     * @return ProgressQuestion
     */
    public function setQuestion(\Hondros\Api\Model\Entity\Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question
     *
     * @return \Hondros\Api\Model\Entity\Question 
     */
    public function getQuestion()
    {
        return $this->question;
    }
}
