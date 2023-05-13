<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
//use Laminas\Filter\FilterProviderInterface;
//use Laminas\Filter\MethodMatchFilter;
//use Laminas\Filter\GetFilter;

//use Laminas\Filter\FilterComposite;
use Laminas\Hydrator\Filter\FilterProviderInterface;
use Laminas\Hydrator\Filter\MethodMatchFilter;
use Laminas\Hydrator\Filter\GetFilter;
use Laminas\Hydrator\Filter\FilterComposite;
use Laminas\Hydrator\Filter\FilterInterface;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity
 * @ORM\EntityListeners({"Hondros\Api\Model\Listener\User"})
 */
class User implements FilterProviderInterface
{
    const ROLE_ADMIN = 'admin';
    const ROLE_CONTENT = 'content';
    const ROLE_MEMBER = 'member';
    const ROLE_GUEST = 'guest';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

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
     * @ORM\Column(name="email", type="string", length=100, precision=0, scale=0, nullable=false, unique=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=32, precision=0, scale=0, nullable=false, unique=true)
     */
    private $token;
    
    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $lastName;

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
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $lastLogin;

    /**
     * @var integer 1 active 0 inactive
     *
     * @ORM\Column(name="status", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", nullable=false, unique=false)
     */
    private $role = self::ROLE_MEMBER;

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
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }
    
    /**
     * Set token
     *
     * @param string $token
     * @return User
     */
    public function setToken($token)
    {
        $this->token = $token;
    
        return $this;
    }
    
    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function generateToken()
    {
        return md5(uniqid($this->getEmail(), true));
    }

    /**
     * Set password
     *
     * @param string $password
     * @param boolean $hash if true we hash the string
     * @return User
     */
    public function setPassword($password, $hash = true)
    {
        if (!$hash) {
            $this->password = $password;
            return $this;
        }
        
        // randomly generate a salt and hash 
        $salt = md5(substr(md5(microtime()),rand(0,26),2));
        
        // figure out hash
        $hash = md5($salt . $password);
        
        // hash:salt
        $this->password = $hash . ":" . $salt;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return User
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
     * @return User
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
     * Set lastLogin
     *
     * @param \DateTime $lastLogin
     * @return User
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return \DateTime 
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return User
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
     * Set role
     *
     * @param integer $role
     * @return User
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return integer
     */
    public function getRole()
    {
        return $this->role;
    }
    
    /**
     * Provides a filter for hydration so we can add or remove what's needed.
     * Defaulting to using getter to extract properties to array.
     *
     * @return FilterInterface
     */
    public function getFilter() : FilterInterface
    {
        $composite = new FilterComposite();
        $composite->addFilter("get", new GetFilter());
    
        $exclusionComposite = new FilterComposite();
    
        $exclusionComposite->addFilter(
            "password",
            new MethodMatchFilter("getPassword"),
            FilterComposite::CONDITION_AND
        );
    
        $composite->addFilter("excludes", $exclusionComposite, FilterComposite::CONDITION_AND);
    
        return $composite;
    }
    
}
