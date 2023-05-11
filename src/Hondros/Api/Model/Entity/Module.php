<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Module
 *
 * @ORM\Table(name="module", indexes={@ORM\Index(name="fk_module_state1_idx", columns={"state_id"}), @ORM\Index(name="fk_module_industry1_idx", columns={"industry_id"}), @ORM\Index(name="fk_module_question_bank2_idx", columns={"study_bank_id"}), @ORM\Index(name="fk_module_question_bank3_idx", columns={"practice_bank_id"}), @ORM\Index(name="fk_module_question_bank4_idx", columns={"exam_bank_id"})})
 * @ORM\Entity
 */
class Module
{
    /**
    * enum values for status
    */
    const STATUS_NEW = 'new';
    const STATUS_IMPORTING = 'importing';
    const STATUS_ACTIVE = 'active';

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
     * @ORM\Column(name="code", type="string", length=10, precision=0, scale=0, nullable=false, unique=true)
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
     * @ORM\Column(name="preassessment_bank_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $preassessmentBankId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="study_bank_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $studyBankId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="practice_bank_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $practiceBankId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="exam_bank_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $examBankId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;
    
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
     * @var string
     *
     * @ORM\Column(name="status", type="string", nullable=false, unique=false)
     */
    private $status;

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
     * @var \Hondros\Api\Model\Entity\Industry
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Industry")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="industry_id", referencedColumnName="id", nullable=false)
     * })
     */
    private $industry;

    /**
     * @var \Hondros\Api\Model\Entity\QuestionBank
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\QuestionBank")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="preassessment_bank_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $preassessmentBank;
    
    /**
     * @var \Hondros\Api\Model\Entity\QuestionBank
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\QuestionBank")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="study_bank_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $studyBank;

    /**
     * @var \Hondros\Api\Model\Entity\QuestionBank
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\QuestionBank")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="practice_bank_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $practiceBank;

    /**
     * @var \Hondros\Api\Model\Entity\QuestionBank
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\QuestionBank")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="exam_bank_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $examBank;
    
    /**
     * @ORM\OneToMany(targetEntity="Hondros\Api\Model\Entity\ExamModule", mappedBy="module")
     **/
    private $examModule;

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
     * @return Module $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Module
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
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
        
        return $this;
    }
    
    /**
     * @return the $code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }
    
	/**
     * @return the $description
     */
    public function getDescription()
    {
        return $this->description;
    }

	/**
     * Set created
     *
     * @param \DateTime $created
     * @return Module
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
     * @return Module
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
     * Set status
     *
     * @param string $status
     * @return Module
     */
    public function setStatus($status)
    {
        if (!in_array($status, [self::STATUS_NEW, self::STATUS_IMPORTING, self::STATUS_ACTIVE])) {
            throw new InvalidArgumentException("Invalid status {$status} passed to Module."); 
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getIndustryId()
    {
        return $this->industryId;
    }

    /**
     * @param number $industryId
     * @return $this
     */
    public function setIndustryId($industryId)
    {
        $this->industryId = $industryId;

        return $this;
    }

    /**
     * @return the $preassessmentBankId
     */
    public function getPreassessmentBankId()
    {
        return $this->preassessmentBankId;
    }
    
    /**
     * @param number $preassessmentBankId
     */
    public function setPreassessmentBankId($preassessmentBankId)
    {
        $this->preassessmentBankId = $preassessmentBankId;
    
        return $this;
    }
    
	/**
     * @return the $studyBankId
     */
    public function getStudyBankId()
    {
        return $this->studyBankId;
    }

	/**
     * @param number $studyBankId
     */
    public function setStudyBankId($studyBankId)
    {
        $this->studyBankId = $studyBankId;
        
        return $this;
    }

	/**
     * @return the $practiceBankId
     */
    public function getPracticeBankId()
    {
        return $this->practiceBankId;
    }

	/**
     * @param number $practiceBankId
     */
    public function setPracticeBankId($practiceBankId)
    {
        $this->practiceBankId = $practiceBankId;
        
        return $this;
    }

	/**
     * @return the $examBankId
     */
    public function getExamBankId()
    {
        return $this->examBankId;
    }

	/**
     * @param number $examBankId
     */
    public function setExamBankId($examBankId)
    {
        $this->examBankId = $examBankId;
        
        return $this;
    }

	/**
     * Set state
     *
     * @param \Hondros\Api\Model\Entity\State $state
     * @return Module
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

    /**
     * Set industry
     *
     * @param \Hondros\Api\Model\Entity\Industry $industry
     * @return Module
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
     * Set preassessmentBank
     *
     * @param \Hondros\Api\Model\Entity\QuestionBank $preassessmentBank
     * @return Module
     */
    public function setPreassessmentBank(\Hondros\Api\Model\Entity\QuestionBank $preassessmentBank = null)
    {
        $this->preassessmentBank = $preassessmentBank;
    
        return $this;
    }
    
    /**
     * Get preassessmentBank
     *
     * @return \Hondros\Api\Model\Entity\QuestionBank
     */
    public function getPreassessmentBank()
    {
        return $this->preassessmentBank;
    }
    
    /**
     * Set studyBank
     *
     * @param \Hondros\Api\Model\Entity\QuestionBank $studyBank
     * @return Module
     */
    public function setStudyBank(\Hondros\Api\Model\Entity\QuestionBank $studyBank = null)
    {
        $this->studyBank = $studyBank;

        return $this;
    }

    /**
     * Get studyBank
     *
     * @return \Hondros\Api\Model\Entity\QuestionBank 
     */
    public function getStudyBank()
    {
        return $this->studyBank;
    }

    /**
     * Set practiceBank
     *
     * @param \Hondros\Api\Model\Entity\QuestionBank $practiceBank
     * @return Module
     */
    public function setPracticeBank(\Hondros\Api\Model\Entity\QuestionBank $practiceBank = null)
    {
        $this->practiceBank = $practiceBank;

        return $this;
    }

    /**
     * Get practiceBank
     *
     * @return \Hondros\Api\Model\Entity\QuestionBank 
     */
    public function getPracticeBank()
    {
        return $this->practiceBank;
    }

    /**
     * Set examBank
     *
     * @param \Hondros\Api\Model\Entity\QuestionBank $examBank
     * @return Module
     */
    public function setExamBank(\Hondros\Api\Model\Entity\QuestionBank $examBank = null)
    {
        $this->examBank = $examBank;

        return $this;
    }

    /**
     * Get examBank
     *
     * @return \Hondros\Api\Model\Entity\QuestionBank 
     */
    public function getExamBank()
    {
        return $this->examBank;
    }
    
    /**
     * Set examModule
     *
     * @param \Hondros\Api\Model\Entity\ExamModule $examModule
     * @return Module
     */
    public function setExamModule(\Hondros\Api\Model\Entity\ExamModule $examModule = null)
    {
        $this->examModule = $examModule;
    
        return $this;
    }
    
    /**
     * Get examModule
     *
     * @return \Hondros\Api\Model\Entity\ExamModule
     */
    public function getExamModule()
    {
        return $this->examModule;
    }
}
