<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QuestionBank
 *
 * @ORM\Table(name="question_bank")
 * @ORM\Entity
 */
class QuestionBank
{
    const TYPE_STUDY = 'study';
    const TYPE_PRACTICE = 'practice';
    const TYPE_EXAM = 'exam';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string study|practice|exam
     *
     * @ORM\Column(name="type", type="string", length=45, precision=0, scale=0, nullable=true, unique=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="question_count", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $questionCount;


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
     * @return QuestionBank
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
    /**
     * Set type
     *
     * @param string $type
     * @return QuestionBank
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
     * @return QuestionBank
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
}
