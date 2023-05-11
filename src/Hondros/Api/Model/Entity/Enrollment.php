<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Enrollment
 *
 * @ORM\Table(name="enrollment", indexes={@ORM\Index(name="fk_enrollment_user_idx", columns={"user_id"}), @ORM\Index(name="fk_enrollment_exam_idx", columns={"exam_id"}), @ORM\Index(name="fk_enrollment_organization_idx", columns={"organization_id"})})
 * @ORM\Entity
 */
class Enrollment
{
    /**
     * Can be accessed
     */
    const STATUS_ACTIVE = 1;

    /**
     * Cannot access
     */
    const STATUS_INACTIVE = 0;

    /**
     * Full access to all features
     */
    const TYPE_FULL = 0;

    /**
     * Limited to free trial restrictions
     */
    const TYPE_TRIAL = 1;

    /**
     * Tracking trial to paid enrollments
     */
    const TYPE_TRIAL_CONVERTED = 2;

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
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="exam_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $examId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="organization_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $organizationId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="external_order_id", type="string", length=10, precision=0, scale=0, nullable=false, unique=true)
     */
    private $externalOrderId;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $started;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $modified;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiration", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $expiration;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="converted", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $converted;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_time", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $totalTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="score", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $score;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="show_preassessment", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $showPreAssessment = true;
    
    /**
     * @var \Hondros\Api\Model\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $user;

    /**
     * @var \Hondros\Api\Model\Entity\Exam
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Exam")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="exam_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $exam;

    /**
     * @var \Hondros\Api\Model\Entity\Organization
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Organization")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $organization;

    /**
     * @ORM\OneToMany(targetEntity="Hondros\Api\Model\Entity\Progress", mappedBy="enrollment")
     **/
    private $progresses;
    
    /**
     * Constructor
     **/
    public function __construct()
    {
        $this->progresses = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return int $userId
     */
    public function getUserId()
    {
        return $this->userId;
    }

	/**
     * @param number $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        
        return $this;
    }

	/**
     * @return int $examId
     */
    public function getExamId()
    {
        return $this->examId;
    }

	/**
     * @param number $examId
     */
    public function setExamId($examId)
    {
        $this->examId = $examId;
        
        return $this;
    }

	/**
     * @return int $organizationId
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }

	/**
     * @param number $organizationId
     */
    public function setOrganizationId($organizationId)
    {
        $this->organizationId = $organizationId;
        
        return $this;
    }

    /**
     * @return string $externalOrderId
     */
    public function getExternalOrderId()
    {
        return $this->externalOrderId;
    }
    
    /**
     * @param number $externalOrderId
     */
    public function setExternalOrderId($externalOrderId)
    {
        $this->externalOrderId = $externalOrderId;
    
        return $this;
    }
    
	/**
     * Set status
     *
     * @param integer $status
     * @return Enrollment
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set type
     *
     * @param boolean $type
     * @return Enrollment
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return boolean 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Enrollment
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
     * Set started
     *
     * @param \DateTime $started
     * @return Enrollment
     */
    public function setStarted($started)
    {
        $this->started = $started;

        return $this;
    }

    /**
     * Get started
     *
     * @return \DateTime 
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * Set modified
     *
     * @param \DateTime $modified
     * @return Enrollment
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
     * Set expiration
     *
     * @param \DateTime $expiration
     * @return Enrollment
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Get expiration
     *
     * @return \DateTime 
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * Set converted
     *
     * @param \DateTime $converted
     * @return Enrollment
     */
    public function setConverted($converted)
    {
        $this->converted = $converted;

        return $this;
    }

    /**
     * Get converted
     *
     * @return \DateTime
     */
    public function getConverted()
    {
        return $this->converted;
    }

    /**
     * Set totalTime
     *
     * @param integer $totalTime
     * @return Enrollment
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
     * Set score
     *
     * @param integer $score
     * @return Enrollment
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
     * @return bool $showPreAssessment
     */
    public function getShowPreAssessment()
    {
        return $this->showPreAssessment;
    }

	/**
     * @param boolean $showPreAssessment
     */
    public function setShowPreAssessment($showPreAssessment)
    {
        $this->showPreAssessment = $showPreAssessment;
        
        return $this;
    }

	/**
     * Set user
     *
     * @param \Hondros\Api\Model\Entity\User $user
     * @return Enrollment
     */
    public function setUser(\Hondros\Api\Model\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Hondros\Api\Model\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set exam
     *
     * @param \Hondros\Api\Model\Entity\Exam $exam
     * @return Enrollment
     */
    public function setExam(\Hondros\Api\Model\Entity\Exam $exam = null)
    {
        $this->exam = $exam;

        return $this;
    }

    /**
     * Get exam
     *
     * @return \Hondros\Api\Model\Entity\Exam 
     */
    public function getExam()
    {
        return $this->exam;
    }

    /**
     * Set organization
     *
     * @param \Hondros\Api\Model\Entity\Organization $organization
     * @return Enrollment
     */
    public function setOrganization(\Hondros\Api\Model\Entity\Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return \Hondros\Api\Model\Entity\Organization 
     */
    public function getOrganization()
    {
        return $this->organization;
    }
    
    /**
     * Add progress
     *
     * @param \Hondros\Api\Model\Entity\Progress $progress
     * @return Enrollment
     */
    public function addProgress(\Hondros\Api\Model\Entity\Progress $progress)
    {
        $this->progresses[] = $progress;
    
        return $this;
    }
    
    /**
     * Remove progress
     *
     * @param \Hondros\Api\Model\Entity\Progress $progress
     */
    public function removeProgress(\Hondros\Api\Model\Entity\Progress $progress)
    {
        $this->progresses->removeElement($progress);
    }
    
    /**
     * Get progresses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProgresses()
    {
        return $this->progresses;
    }

    /**
     * Make sure this is a valid type
     *
     * @param int $type
     * @return bool
     */
    public function isValidType($type)
    {
        $type = filter_var($type, FILTER_VALIDATE_INT);
        if ($type === false) {
            return false;
        }

        // need to check all constants - can replace this code in php 5.6
        $class = new \ReflectionClass(__CLASS__);
        $constants = $class->getConstants();

        foreach ($constants as $key => $value) {
            if (substr($key, 0, 5) == 'TYPE_' && $value == $type) {
                return true;
            }
        }

        return false;
    }

}
