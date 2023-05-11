<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ModuleAttemptQuestion
 *
 * @ORM\Table(name="module_attempt_question", indexes={@ORM\Index(name="fk_study_attempt_question_question1_idx", columns={"question_id"}), @ORM\Index(name="fk_study_attempt_question_module_attempt1_idx", columns={"module_attempt_id"})})
 * @ORM\Entity
 */
class ModuleAttemptQuestion
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
     * @ORM\Column(name="module_attempt_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $moduleAttemptId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="view", type="string", length=45, precision=0, scale=0, nullable=true, unique=false)
     */
    private $view;

    /**
     * @var integer
     *
     * @ORM\Column(name="viewed", type="boolean", precision=0, scale=0, nullable=true, unique=false)
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
     * @var string
     *
     * @ORM\Column(name="answer", type="string", length=50, precision=0, scale=0, nullable=true, unique=false)
     */
    private $answer;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sort;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $modified;

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
     * @var \Hondros\Api\Model\Entity\ModuleAttempt
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\ModuleAttempt")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="module_attempt_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $moduleAttempt;

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
     * @return the $moduleAttemptId
     */
    public function getModuleAttemptId()
    {
        return $this->moduleAttemptId;
    }
    
    /**
     * @param number $moduleAttemptId
     */
    public function setModuleAttemptId($moduleAttemptId)
    {
        $this->moduleAttemptId = $moduleAttemptId;
        
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
     * Set view
     *
     * @param string $view
     * @return ModuleAttemptQuestion
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Get view
     *
     * @return string 
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set viewed
     *
     * @param integer $viewed
     * @return ModuleAttemptQuestion
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
     * @return ModuleAttemptQuestion
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
     * @return ModuleAttemptQuestion
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
     * @return ModuleAttemptQuestion
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
     * Set answer
     *
     * @param string $answer
     * @return ModuleAttemptQuestion
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;

        return $this;
    }

    /**
     * Get answer
     *
     * @return string 
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     * @return ModuleAttemptQuestion
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer 
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return ModuleAttemptQuestion
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set modified
     *
     * @param \DateTime $modified
     * @return ModuleAttemptQuestion
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified
     *
     * @return \DateTime 
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set question
     *
     * @param \Hondros\Api\Model\Entity\Question $question
     * @return ModuleAttemptQuestion
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

    /**
     * Set moduleAttempt
     *
     * @param \Hondros\Api\Model\Entity\ModuleAttempt $moduleAttempt
     * @return ModuleAttemptQuestion
     */
    public function setModuleAttempt(\Hondros\Api\Model\Entity\ModuleAttempt $moduleAttempt = null)
    {
        $this->moduleAttempt = $moduleAttempt;

        return $this;
    }

    /**
     * Get moduleAttempt
     *
     * @return \Hondros\Api\Model\Entity\ModuleAttempt 
     */
    public function getModuleAttempt()
    {
        return $this->moduleAttempt;
    }
}
