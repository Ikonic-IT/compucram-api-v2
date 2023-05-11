<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserLog
 *
 * @ORM\Table(name="user_log", indexes={@ORM\Index(name="fk_user_log_user1_idx", columns={"user_id"})})
 * @ORM\Entity
 */
class UserLog
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
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="exam_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $examId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="exam_module_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $examModuleId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="exam", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    //private $exam;

    /**
     * @var integer
     *
     * @ORM\Column(name="module", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    //private $examModule;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=50, precision=0, scale=0, nullable=false, unique=false)
     */
    private $action;

    /**
     * @var string
     *
     * @ORM\Column(name="info", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $info;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=10, precision=0, scale=0, nullable=true, unique=false)
     */
    private $version;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $created;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return the $userId
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
     * @return the $examId
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
     * @return the $examModuleId
     */
    public function getExamModuleId()
    {
        return $this->examModuleId;
    }
    
    /**
     * @param number $examModuleId
     */
    public function setExamModuleId($examModuleId)
    {
        $this->examModuleId = $examModuleId;
    
        return $this;
    }
    
//     /**
//      * Set exam
//      *
//      * @param integer $exam
//      * @return UserLog
//      */
//     public function setExam($exam)
//     {
//         $this->exam = $exam;

//         return $this;
//     }

//     /**
//      * Get exam
//      *
//      * @return integer 
//      */
//     public function getExam()
//     {
//         return $this->exam;
//     }

//     /**
//      * Set module
//      *
//      * @param integer $module
//      * @return UserLog
//      */
//     public function setModule($module)
//     {
//         $this->module = $module;

//         return $this;
//     }

//     /**
//      * Get module
//      *
//      * @return integer 
//      */
//     public function getModule()
//     {
//         return $this->module;
//     }

    /**
     * Set action
     *
     * @param string $action
     * @return UserLog
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set info
     *
     * @param string $info
     * @return UserLog
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return string 
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return UserLog
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return UserLog
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
     * Set user
     *
     * @param \Hondros\Api\Model\Entity\User $user
     * @return UserLog
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
}
