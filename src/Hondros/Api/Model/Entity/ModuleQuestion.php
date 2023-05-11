<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ModuleQuestion
 *
 * @ORM\Table(name="module_question")
 * @ORM\Entity
 * @ORM\EntityListeners({"Hondros\Api\Model\Listener\ModuleQuestion"})
 */
class ModuleQuestion
{
    const TYPE_STUDY = 'study';
    const TYPE_PRACTICE = 'practice';
    const TYPE_EXAM = 'exam';
    const TYPE_PREASSESSMENT = 'preassessment';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="module_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $moduleId;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $questionId;

    /**
     * @var string

     * @ORM\Column(name="type", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var \Hondros\Api\Model\Entity\Module
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Module")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="module_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $module;

    /**
     * @var \Hondros\Api\Model\Entity\Enrollment
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Question")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="question_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $question;

    /**
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ModuleQuestion
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int $questionId
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * @param int $questionId
     * @return ModuleQuestion
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;
        
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
     * @return ModuleQuestion
     */
    public function setModuleId($moduleId)
    {
        $this->moduleId = $moduleId;
        
        return $this;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return ModuleQuestion
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
     * Set module
     *
     * @param Module $module
     * @return ModuleQuestion
     */
    public function setModule(Module $module = null)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get module
     *
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set question
     *
     * @param Question $question
     * @return ModuleQuestion
     */
    public function setQuestion(Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question
     *
     * @return Question
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @return array
     */
    public static function getValidTypes()
    {
        return [
            self::TYPE_PREASSESSMENT,
            self::TYPE_STUDY,
            self::TYPE_PRACTICE,
            self::TYPE_EXAM
        ];
    }
}
