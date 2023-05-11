<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Exam
 *
 * @ORM\Table(name="exam", indexes={@ORM\Index(name="fk_exam_industry_idx", columns={"industry_id"}), @ORM\Index(name="fk_exam_state_idx", columns={"state_id"})})
 * @ORM\Entity
 */
class Exam
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
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10, precision=0, scale=0, nullable=true, unique=false)
     */
    private $code;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="industry_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $industryId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="state_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $stateId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $name;
    
    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=500, precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var integer number of seconds student has to complete a simulated exam attempt
     *
     * @ORM\Column(name="exam_time", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $examTime = null;
    
    /**
     * @var integer number of days a student has to complete the exam before it expires
     *
     * @ORM\Column(name="access_time", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $accessTime = null;

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
     * @var \Hondros\Api\Model\Entity\Industry
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Industry")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="industry_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $industry;

    /**
     * @var \Hondros\Api\Model\Entity\State
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\State")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $state;


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
     * @param int $id
     * @return Exam $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Exam
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set industryId
     *
     * @param integer $industryId
     * @return Exam
     */
    public function setIndustryId($industryId)
    {
        $this->industryId = $industryId;
    
        return $this;
    }
    
    /**
     * Get industryId
     *
     * @return integer
     */
    public function getIndustryId()
    {
        return $this->industryId;
    }
    
    /**
     * Set stateId
     *
     * @param integer $stateId
     * @return Exam
     */
    public function setStateId($stateId)
    {
        $this->stateId = $stateId;
    
        return $this;
    }
    
    /**
     * Get stateId
     *
     * @return integer
     */
    public function getStateId()
    {
        return $this->stateId;
    }
    
    /**
     * Set name
     *
     * @param string $name
     * @return Exam
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set description
     *
     * @param string $description
     * @return Exam
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }
    
    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set examTime
     *
     * @param integer $examTime
     * @return Exam
     */
    public function setExamTime($examTime)
    {
        $this->examTime = $examTime;

        return $this;
    }

    /**
     * Get examTime how long students have to take the exam in seconds
     *
     * @return integer 
     */
    public function getExamTime()
    {
        return $this->examTime;
    }
    
    /**
     * Set accessTime
     *
     * @param integer $accessTime
     * @return Exam
     */
    public function setAccessTime($accessTime)
    {
        $this->accessTime = $accessTime;
    
        return $this;
    }
    
    /**
     * Get accessTime
     *
     * @return integer
     */
    public function getAccessTime()
    {
        return $this->accessTime;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Exam
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
     * @return Exam
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
     * Set industry
     *
     * @param \Hondros\Api\Model\Entity\Industry $industry
     * @return Exam
     */
    public function setIndustry(\Hondros\Api\Model\Entity\Industry $industry = null)
    {
        $this->industry = $industry;

        return $this;
    }

    /**
     * Get industry
     *
     * @return \Hondros\Api\Model\Entity\Industry 
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * Set state
     *
     * @param \Hondros\Api\Model\Entity\State $state
     * @return Exam
     */
    public function setState(\Hondros\Api\Model\Entity\State $state = null)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return \Hondros\Api\Model\Entity\State 
     */
    public function getState()
    {
        return $this->state;
    }
}
