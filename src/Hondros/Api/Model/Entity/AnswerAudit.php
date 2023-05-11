<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QuestionAudit
 *
 * @ORM\Table(name="answer_audit")
 * @ORM\Entity
 */
class AnswerAudit
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
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @var string
     *
     * @ORM\Column(name="answer_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $answerId;

    /**
     * @var string
     *
     * @ORM\Column(name="column_name", type="string", length=30, precision=0, scale=0, nullable=false, unique=false)
     */
   private $columnName;

    /**
     * @var string
     *
     * @ORM\Column(name="before_value", type="text")
     */
    private $beforeValue;

    /**
     * @var string
     *
     * @ORM\Column(name="after_value", type="text")
     */
    private $afterValue;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $modified;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return QuestionAudit
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getAnswerId()
    {
        return $this->answerId;
    }

    /**
     * @param string $answerId
     * @return AnswerAudit
     */
    public function setAnswerId($answerId)
    {
        $this->answerId = $answerId;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * @param string $questionId
     * @return QuestionAudit
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param string $columnName
     * @return QuestionAudit
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
        return $this;
    }

    /**
     * @return string
     */
    public function getBeforeValue()
    {
        return $this->beforeValue;
    }

    /**
     * @param string $beforeValue
     * @return QuestionAudit
     */
    public function setBeforeValue($beforeValue)
    {
        $this->beforeValue = $beforeValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getAfterValue()
    {
        return $this->afterValue;
    }

    /**
     * @param string $afterValue
     * @return QuestionAudit
     */
    public function setAfterValue($afterValue)
    {
        $this->afterValue = $afterValue;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param \DateTime $modified
     * @return QuestionAudit
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
        return $this;
    }

}
