<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hondros\Api\Util\Helper\StringUtil;

/**
 * Answer
 *
 * @ORM\Table(name="answer", indexes={@ORM\Index(name="fk_question_answer_question1_idx", columns={"question_id"})})
 * @ORM\Entity
 * @ORM\EntityListeners({"Hondros\Api\Model\Listener\Answer"})
 */
class Answer
{
    use StringUtil { convertStringToUtf8 as protected; }

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
     * @ORM\Column(name="question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionId;

    /**
     * @var string
     *
     * @ORM\Column(name="answer_text", type="string", length=1000, precision=0, scale=0, nullable=false, unique=false)
     */
    private $answerText;

    /**
     * @var boolean
     *
     * @ORM\Column(name="correct", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $correct;

    /**
     * @var string
     *
     * @ORM\Column(name="audio_hash", type="string", length=32, precision=0, scale=0, nullable=true, unique=false)
     */
    private $audioHash;
    
    /**
     * @var string
     *
     * @ORM\Column(name="audio_file", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $audioFile;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_by", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $createdById;

    /**
     * @var integer
     *
     * @ORM\Column(name="modified_by", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $modifiedById;
    
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
     * @var \Hondros\Api\Model\Entity\Question
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Question",  inversedBy="answers")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="question_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $question;

    /**
     * @var \Hondros\Api\Model\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=true)
     * })
     */
    private $createdBy;

    /**
     * @var \Hondros\Api\Model\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="modified_by", referencedColumnName="id", nullable=true)
     * })
     */
    private $modifiedBy;


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
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getQuestionId()
    {
        return $this->questionId;
    }

    /**
     * @param int $questionId
     * @return Answer
     */
    public function setQuestionId($questionId)
    {
        $this->questionId = $questionId;
        return $this;
    }

    /**
     * Set answerText
     * @todo add utf8 cleanup
     *
     * @param string $answerText
     * @return Answer
     */
    public function setAnswerText($answerText)
    {
        $this->answerText = $this->convertStringToUtf8($answerText);

        return $this;
    }

    /**
     * Get answerText
     *
     * @return string 
     */
    public function getAnswerText()
    {
        return $this->answerText;
    }

    /**
     * Set correct
     *
     * @param boolean $correct
     * @return Answer
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
     * Set audio hash
     *
     * @param string $audioHash
     * @return Answer
     */
    public function setAudioHash($audioHash)
    {
        $this->audioHash = trim($audioHash) === '' ? null : $audioHash;
    
        return $this;
    }
    
    /**
     * Get audio hash
     *
     * @return string
     */
    public function getAudioHash()
    {
        return $this->audioHash;
    }
    
    /**
     * Set audio file
     *
     * @param string $audioFile
     * @return Answer
     */
    public function setAudioFile($audioFile)
    {
        $this->audioFile = trim($audioFile) === '' ? null : $audioFile;
    
        return $this;
    }
    
    /**
     * Get audio file
     *
     * @return string
     */
    public function getAudioFile()
    {
        return $this->audioFile;
    }

    /**
     * @return int
     */
    public function getCreatedById()
    {
        return $this->createdById;
    }

    /**
     * @param int $createdById
     * @return Answer
     */
    public function setCreatedById($createdById)
    {
        $this->createdById = $createdById;

        return $this;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Answer
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
     * @return int
     */
    public function getModifiedById()
    {
        return $this->modifiedById;
    }

    /**
     * @param int $modifiedById
     * @return Answer
     */
    public function setModifiedById($modifiedById)
    {
        $this->modifiedById = $modifiedById;

        return $this;
    }

    /**
     * Set modified
     *
     * @param \DateTime $modified
     * @return Answer
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
     * Set question
     *
     * @param \Hondros\Api\Model\Entity\Question $question
     * @return Answer
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
     * Set createdBy
     *
     * @param \Hondros\Api\Model\Entity\User $createdBy
     * @return Question
     */
    public function setCreatedBy(User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Hondros\Api\Model\Entity\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy
     *
     * @param \Hondros\Api\Model\Entity\User $modifiedBy
     * @return Question
     */
    public function setModifiedBy(User $modifiedBy = null)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return \Hondros\Api\Model\Entity\User
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }
}
