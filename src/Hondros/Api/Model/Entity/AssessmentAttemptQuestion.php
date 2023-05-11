<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AssessmentAttemptQuestion
 *
 * @ORM\Table(name="assessment_attempt_question", indexes={@ORM\Index(name="fk_assessment_attempt_question_assessment_attempt1_idx", columns={"assessment_attempt_id"}), @ORM\Index(name="fk_assessment_attempt_question_question1_idx", columns={"question_id"}), @ORM\Index(name="fk_assessment_attempt_question_module1_idx", columns={"module_id"})})
 * @ORM\Entity
 */
class AssessmentAttemptQuestion
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
     * @ORM\Column(name="assessment_attempt_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $assessmentAttemptId;

    /**
     * @var integer
     *
     * @ORM\Column(name="module_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $moduleId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="view", type="smallint", precision=0, scale=0, nullable=true, unique=false)
     */
    private $view;

    /**
     * @var boolean
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
     * @var \Hondros\Api\Model\Entity\AssessmentAttempt
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\AssessmentAttempt", inversedBy="assessmentAttemptQuestions")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="assessment_attempt_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $assessmentAttempt;

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
     * @var \Hondros\Api\Model\Entity\Module
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Module")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="module_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $module;


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
     * @return the $assessmentAttemptId
     */
    public function getAssessmentAttemptId()
    {
        return $this->assessmentAttemptId;
    }
    
    /**
     * @param number $assessmentAttemptId
     */
    public function setAssessmentAttemptId($assessmentAttemptId)
    {
        $this->assessmentAttemptId = $assessmentAttemptId;
    
        return $this;
    }

    /**
     * Set moduleId
     *
     * @param integer $moduleId
     * @return AssessmentAttemptQuestion
     */
    public function setModuleId($moduleId)
    {
        $this->moduleId = $moduleId;
    
        return $this;
    }
    
    /**
     * Get moduleId
     *
     * @return integer
     */
    public function getModuleId()
    {
        return $this->moduleId;
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
     * @param integer $view
     * @return AssessmentAttemptQuestion
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Get view
     *
     * @return integer 
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set viewed
     *
     * @param boolean $viewed
     * @return AssessmentAttemptQuestion
     */
    public function setViewed($viewed)
    {
        $this->viewed = $viewed;

        return $this;
    }

    /**
     * Get viewed
     *
     * @return boolean 
     */
    public function getViewed()
    {
        return $this->viewed;
    }

    /**
     * Set answered
     *
     * @param boolean $answered
     * @return AssessmentAttemptQuestion
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
     * @return AssessmentAttemptQuestion
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
     * @return AssessmentAttemptQuestion
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
     * @return AssessmentAttemptQuestion
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
     * @return AssessmentAttemptQuestion
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
     * @return AssessmentAttemptQuestion
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
     * @return AssessmentAttemptQuestion
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
     * Set assessmentAttempt
     *
     * @param \Hondros\Api\Model\Entity\AssessmentAttempt $assessmentAttempt
     * @return AssessmentAttemptQuestion
     */
    public function setAssessmentAttempt(\Hondros\Api\Model\Entity\AssessmentAttempt $assessmentAttempt = null)
    {
        $this->assessmentAttempt = $assessmentAttempt;

        return $this;
    }

    /**
     * Get assessmentAttempt
     *
     * @return \Hondros\Api\Model\Entity\AssessmentAttempt 
     */
    public function getAssessmentAttempt()
    {
        return $this->assessmentAttempt;
    }

    /**
     * Set question
     *
     * @param \Hondros\Api\Model\Entity\Question $question
     * @return AssessmentAttemptQuestion
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
     * Set module
     *
     * @param \Hondros\Api\Model\Entity\Module $module
     * @return AssessmentAttemptQuestion
     */
    public function setModule(\Hondros\Api\Model\Entity\Module $module = null)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get module
     *
     * @return \Hondros\Api\Model\Entity\Module 
     */
    public function getModule()
    {
        return $this->module;
    }
}
