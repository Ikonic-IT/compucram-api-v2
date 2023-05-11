<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExamModule
 *
 * @ORM\Table(name="exam_module", indexes={@ORM\Index(name="fk_exam_has_module_module1_idx", columns={"module_id"}), @ORM\Index(name="fk_exam_has_module_exam1_idx", columns={"exam_id"})})
 * @ORM\Entity
 */
class ExamModule
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
     * @ORM\Column(name="exam_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $examId;

    /**
     * @var integer
     *
     * @ORM\Column(name="module_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $moduleId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="preassessment_questions", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $preassessmentQuestions;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="practice_questions", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $practiceQuestions;

    /**
     * @var integer
     *
     * @ORM\Column(name="exam_questions", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $examQuestions;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", precision=0, scale=0, nullable=true, unique=false)
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
     * @var \Hondros\Api\Model\Entity\Exam
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Exam")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="exam_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $exam;

    /**
     * @var \Hondros\Api\Model\Entity\Module
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Module", inversedBy="examModule")
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
     * @param int $id
     * @return ExamModule $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set examId
     *
     * @param integer $examId
     * @return ExamModule
     */
    public function setExamId($examId)
    {
        $this->examId = $examId;

        return $this;
    }

    /**
     * Get examId
     *
     * @return integer
     */
    public function getExamId()
    {
        return $this->examId;
    }

    /**
     * Set moduleId
     *
     * @param integer $moduleId
     * @return ExamModule
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
     * Set name
     *
     * @param string $name
     * @return ExamModule
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
     * Set preassessmentQuestions
     *
     * @param integer $preassessmentQuestions
     * @return ExamModule
     */
    public function setPreassessmentQuestions($preassessmentQuestions)
    {
        $this->preassessmentQuestions = $preassessmentQuestions;

        return $this;
    }

    /**
     * Get preassessmentQuestions
     *
     * @return integer 
     */
    public function getPreassessmentQuestions()
    {
        return $this->preassessmentQuestions;
    }

    /**
     * Set practiceQuestions
     *
     * @param integer $practiceQuestions
     * @return ExamModule
     */
    public function setPracticeQuestions($practiceQuestions)
    {
        $this->practiceQuestions = $practiceQuestions;

        return $this;
    }

    /**
     * Get practiceQuestions
     *
     * @return integer 
     */
    public function getPracticeQuestions()
    {
        return $this->practiceQuestions;
    }

    /**
     * Set examQuestions
     *
     * @param integer $examQuestions
     * @return ExamModule
     */
    public function setExamQuestions($examQuestions)
    {
        $this->examQuestions = $examQuestions;

        return $this;
    }

    /**
     * Get examQuestions
     *
     * @return integer 
     */
    public function getExamQuestions()
    {
        return $this->examQuestions;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     * @return ExamModule
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
     * @return ExamModule
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
     * @return ExamModule
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
     * Set exam
     *
     * @param \Hondros\Api\Model\Entity\Exam $exam
     * @return ExamModule
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
     * Set module
     *
     * @param \Hondros\Api\Model\Entity\Module $module
     * @return ExamModule
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
