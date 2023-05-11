<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hondros\Api\Util\Helper\StringUtil;

/**
 * Question
 *
 * @ORM\Table(name="question", indexes={@ORM\Index(name="question_type_idx", columns={"type"}), @ORM\Index(name="fk_question_question_bank1_idx", columns={"question_bank_id"})})
 * @ORM\Entity
 * @ORM\EntityListeners({"Hondros\Api\Model\Listener\Question"})
*/
class Question
{
    use StringUtil { convertStringToUtf8 as protected; }

    const TYPE_VOCAB = 'vocab';
    const TYPE_MULTI = 'multi';

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
     * @ORM\Column(name="question_bank_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionBankId;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var string
     *
     * @see http://rogerwilliamsmedia.com/styling-ordered-lists-with-letters-in-wordpress/ for <ol> styling
     * @ORM\Column(name="question_text", type="string", length=1000, precision=0, scale=0, nullable=false, unique=false)
     */
    private $questionText;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback", type="string", length=45, precision=0, scale=0, nullable=true, unique=false)
     */
    private $feedback;

    /**
     * @var string
     *
     * @ORM\Column(name="techniques", type="string", length=45, precision=0, scale=0, nullable=true, unique=false)
     */
    private $techniques;

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
     * @var integer
     *
     * @ORM\Column(name="active", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $active = true;

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
     * @var \Hondros\Api\Model\Entity\QuestionBank
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\QuestionBank")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="question_bank_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $questionBank;

    /**
     * @ORM\OneToMany(targetEntity="Hondros\Api\Model\Entity\Answer", mappedBy="question")
     **/
    private $answers;

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
     * Constructor
     **/
    public function __construct()
    {
        $this->answers = new \Doctrine\Common\Collections\ArrayCollection();
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
    public function getQuestionBankId()
    {
        return $this->questionBankId;
    }

    /**
     * @param int $questionBankId
     * @return Question
     */
    public function setQuestionBankId($questionBankId)
    {
        $this->questionBankId = $questionBankId;
        return $this;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Question
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
     * Set questionText
     *
     * @todo add utf8 cleanup
     * @param string $questionText
     * @return Question
     */
    public function setQuestionText($questionText)
    {
        $this->questionText = $this->convertStringToUtf8($questionText);

        return $this;
    }

    /**
     * Get questionText
     *
     * @return string 
     */
    public function getQuestionText()
    {
        return $this->questionText;
    }

    /**
     * Set feedback
     *
     * @param string $feedback
     * @return Question
     */
    public function setFeedback($feedback)
    {
        $this->feedback = trim($feedback) === '' ? null : $this->convertStringToUtf8($feedback);

        return $this;
    }
    
    /**
     * Get feedback
     *
     * @return string
     */
    public function getFeedback()
    {
        return $this->feedback;
    }
    
    /**
     * Set techniques
     *
     * @param string $techniques
     * @return Question
     */
    public function setTechniques($techniques)
    {
        $this->techniques = trim($techniques) === '' ? null : $this->convertStringToUtf8($techniques);

        return $this;
    }

    /**
     * Get techniques
     *
     * @return string 
     */
    public function getTechniques()
    {
        return $this->techniques;
    }

    /**
     * Set audio hash
     *
     * @param string $audioHash
     * @return Question
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
     * @return Question
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
     * @return Question
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
     * @return Question
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
     * @return Question
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
     * @return Question
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
     * Set active
     *
     * @param boolean $active
     * @return Question
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set questionBank
     *
     * @param \Hondros\Api\Model\Entity\QuestionBank $questionBank
     * @return Question
     */
    public function setQuestionBank(\Hondros\Api\Model\Entity\QuestionBank $questionBank = null)
    {
        $this->questionBank = $questionBank;

        return $this;
    }

    /**
     * Get questionBank
     *
     * @return \Hondros\Api\Model\Entity\QuestionBank 
     */
    public function getQuestionBank()
    {
        return $this->questionBank;
    }
    
    /**
     * Add answer
     *
     * @param Answer $answer
     * @return Question
     */
    public function addAnswer(Answer $answer)
    {
        $this->answers->add($answer);
    
        return $this;
    }
    
    /**
     * Remove answer
     *
     * @param Answer $answer
     */
    public function removeAnswer(Answer $answer)
    {
        $this->answers->removeElement($answer);
    }
    
    /**
     * Get answers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnswers()
    {
        return $this->answers;
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
