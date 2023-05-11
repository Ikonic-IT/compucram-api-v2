<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ModuleAttempt
 *
 * @ORM\Table(name="module_attempt", indexes={@ORM\Index(name="fk_module_vocabulary_attempt_module1_idx", columns={"module_id"}), @ORM\Index(name="fk_module_vocabulary_attempt_enrollment1_idx", columns={"enrollment_id"})})
 * @ORM\Entity
 */
class ModuleAttempt
{
    const TYPE_STUDY = 'study';
    const TYPE_PRACTICE = 'practice';

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
     * @ORM\Column(name="enrollment_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $enrollmentId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="module_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
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
     * @ORM\Column(name="score", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $score;

    /**
     * @var integer
     *
     * @ORM\Column(name="bookmarked", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $bookmarked = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="unbookmarked", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $unbookmarked = 0;

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
     * @var \DateTime
     *
     * @ORM\Column(name="completed", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $completed;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_time", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $totalTime = 0;

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
     * @var \Hondros\Api\Model\Entity\Enrollment
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Enrollment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="enrollment_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $enrollment;


    /**
     * @return int $enrollmentId
     */
    public function getEnrollmentId()
    {
        return $this->enrollmentId;
    }

    /**
     * @param number $enrollmentId
     * @return ModuleAttempt
     */
    public function setEnrollmentId($enrollmentId)
    {
        $this->enrollmentId = $enrollmentId;
        
        return $this;
    }

    /**
     * @return int $moduleId
     */
    public function getModuleId()
    {
        return $this->moduleId;
    }

    /**
     * @param number $moduleId
     * @return ModuleAttempt
     */
    public function setModuleId($moduleId)
    {
        $this->moduleId = $moduleId;
        
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
     * Set type
     *
     * @param string $type
     * @return ModuleAttempt
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
     * @return ModuleAttempt
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
     * Set correct
     *
     * @param integer $correct
     * @return ModuleAttempt
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
     * @return ModuleAttempt
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
     * Set score
     *
     * @param integer $score
     * @return ModuleAttempt
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
     * Set bookmarked
     *
     * @param integer $bookmarked
     * @return ModuleAttempt
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
     * Set unbookmarked
     *
     * @param integer $unbookmarked
     * @return ModuleAttempt
     */
    public function setUnbookmarked($unbookmarked)
    {
        $this->unbookmarked = $unbookmarked;

        return $this;
    }

    /**
     * Get unbookmarked
     *
     * @return integer 
     */
    public function getUnbookmarked()
    {
        return $this->unbookmarked;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return ModuleAttempt
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
     * @return ModuleAttempt
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
     * Set completed
     *
     * @param \DateTime $completed
     * @return ModuleAttempt
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;

        return $this;
    }

    /**
     * Get completed
     *
     * @return \DateTime 
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * Set totalTime
     *
     * @param integer $totalTime
     * @return ModuleAttempt
     */
    public function setTotalTime($totalTime)
    {
        $this->totalTime = $totalTime;

        return $this;
    }

    /**
     * Get totalTime
     *
     * @return integer 
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * Set module
     *
     * @param \Hondros\Api\Model\Entity\Module $module
     * @return ModuleAttempt
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
     * Set enrollment
     *
     * @param \Hondros\Api\Model\Entity\Enrollment $enrollment
     * @return ModuleAttempt
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
}
