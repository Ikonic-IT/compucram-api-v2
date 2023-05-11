<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Progress
 *
 * @ORM\Table(name="progress", indexes={@ORM\Index(name="fk_progress_enrollment1_idx", columns={"enrollment_id"}), @ORM\Index(name="fk_progress_module1_idx", columns={"module_id"})})
 * @ORM\Entity
 */
class Progress
{
    const TYPE_STUDY = 'study';

    const TYPE_PRACTICE = 'practice';

    /**
     * used to calculate the score for practice progress
     */
    const PRACTICE_SCORE_MULTIPLIER = .25;

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
     * @ORM\Column(name="enrollment_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $enrollmentId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="module_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $moduleId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="question_count", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionCount;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="attempts", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $attempts = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="correct", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $correct = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="incorrect", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $incorrect = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="bookmarked", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $bookmarked = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="score", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $score = 0;

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
     * @var \Hondros\Api\Model\Entity\Enrollment
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Enrollment", inversedBy="progresses")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="enrollment_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $enrollment;

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
     * @ORM\OneToMany(targetEntity="Hondros\Api\Model\Entity\ProgressQuestion", mappedBy="progress")
     **/
    private $questions;

    /**
     * Constructor
     **/
    public function __construct()
    {
        $this->questions = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set enrollmentId
     *
     * @param integer $enrollmentId
     * @return Progress
     */
    public function setEnrollmentId($enrollmentId)
    {
        $this->enrollmentId = $enrollmentId;

        return $this;
    }

    /**
     * Get enrollmentId
     *
     * @return integer
     */
    public function getEnrollmentId()
    {
        return $this->enrollmentId;
    }
    
    /**
     * Set moduleId
     *
     * @param integer $moduleId
     * @return Progress
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
     * Set type
     *
     * @param string $type
     * @return Progress
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }
    
    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * Set questionCount
     *
     * @param integer $questionCount
     * @return Progress
     */
    public function setQuestionCount($questionCount)
    {
        $this->questionCount = $questionCount;
    
        return $this;
    }
    
    /**
     * Get questionCount
     *
     * @return integer
     */
    public function getQuestionCount()
    {
        return $this->questionCount;
    }
    
    /**
     * Set attempts
     *
     * @param integer $attempts
     * @return Progress
     */
    public function setAttempts($attempts)
    {
        $this->attempts = $attempts;

        return $this;
    }

    /**
     * Get attempts
     *
     * @return integer 
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * Set correct
     *
     * @param integer $correct
     * @return Progress
     */
    public function setCorrect($correct)
    {
        $this->correct = $correct;

        return $this;
    }

    /**
     * Get correct
     *
     * @return integer 
     */
    public function getCorrect()
    {
        return $this->correct;
    }

    /**
     * Set incorrect
     *
     * @param integer $incorrect
     * @return Progress
     */
    public function setIncorrect($incorrect)
    {
        $this->incorrect = $incorrect;

        return $this;
    }

    /**
     * Get incorrect
     *
     * @return integer 
     */
    public function getIncorrect()
    {
        return $this->incorrect;
    }

    /**
     * Set bookmarked
     *
     * @param integer $bookmarked
     * @return Progress
     */
    public function setBookmarked($bookmarked)
    {
        $this->bookmarked = $bookmarked;

        return $this;
    }

    /**
     * Get bookmarked
     *
     * @return integer 
     */
    public function getBookmarked()
    {
        return $this->bookmarked;
    }

    /**
     * Set score
     *
     * @param integer $score
     * @return Progress
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return integer 
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Progress
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
     * @return Progress
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
     * Set enrollment
     *
     * @param \Hondros\Api\Model\Entity\Enrollment $enrollment
     * @return Progress
     */
    public function setEnrollment(\Hondros\Api\Model\Entity\Enrollment $enrollment = null)
    {
        $this->enrollment = $enrollment;

        return $this;
    }

    /**
     * Get enrollment
     *
     * @return \Hondros\Api\Model\Entity\Enrollment 
     */
    public function getEnrollment()
    {
        return $this->enrollment;
    }

    /**
     * Set module
     *
     * @param \Hondros\Api\Model\Entity\Module $module
     * @return Progress
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
    
    /**
     * Add question
     *
     * @param \Hondros\Api\Model\Entity\Question $question
     * @return Progress
     */
    public function addQuestion(\Hondros\Api\Model\Entity\Question $question)
    {
        $this->questions[] = $question;
    
        return $this;
    }
    
    /**
     * Remove question
     *
     * @param \Hondros\Api\Model\Entity\Question $question
     */
    public function removeQuestion(\Hondros\Api\Model\Entity\Question $question)
    {
        $this->questions->removeElement($question);
    }
    
    /**
     * Get questions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuestions()
    {
        return $this->questions;
    }
}
