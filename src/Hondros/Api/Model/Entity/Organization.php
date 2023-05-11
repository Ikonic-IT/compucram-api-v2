<?php

namespace Hondros\Api\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Organization
 *
 * @ORM\Table(name="organization")
 * @ORM\Entity
 */
class Organization
{
    const COMPUCRAM = 'CompuCram';

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
     * @ORM\Column(name="parent_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $parentId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=150, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=1000, precision=0, scale=0, nullable=false, unique=false)
     */
    private $url;
    
    /**
     * @var string
     *
     * @ORM\Column(name="redirect_url", type="string", length=1000, precision=0, scale=0, nullable=false, unique=false)
     */
    private $redirectUrl;

    /**
     * @var integer
     *
     * @ORM\Column(name="credits", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $credits;
    
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
     * @var \Doctrine\Common\Collections\ArrayCollection
     * 
     * @ORM\OneToMany(targetEntity="Hondros\Api\Model\Entity\Organization", mappedBy="parent")
     */
    private $children;
    
    /**
     * @var \Hondros\Api\Model\Entity\Organization
     *
     * @ORM\ManyToOne(targetEntity="Hondros\Api\Model\Entity\Organization", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

    /**
     * Constructor
     **/
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param int $id
     * @return Organization
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     * @return Organization
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    
        return $this;
    }
    
    /**
     * Get parentId
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }
    
    /**
     * Set name
     *
     * @param string $name
     * @return Organization
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
     * Set url
     *
     * @param string $url
     * @return Organization
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set redirect url
     *
     * @param string $redirectUrl
     * @return Organization
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     * Get redirect url
     *
     * @return string 
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Set credits
     *
     * @param integer $credits
     * @return Organization
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * Get credits
     *
     * @return integer 
     */
    public function getCredits()
    {
        return $this->credits;
    }
    
    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Organization
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
     * @return Organization
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
     * Set parent
     *
     * @param \Hondros\Api\Model\Entity\Organization $parent
     * @return Organization
     */
    public function setParent(\Hondros\Api\Model\Entity\Organization $parent = null)
    {
        $this->parent = $parent;
    
        return $this;
    }
    
    /**
     * Get parent
     *
     * @return \Hondros\Api\Model\Entity\Organization
     */
    public function getParent()
    {
        return $this->parent;
    }
    
    /**
     * Add child
     *
     * @param \Hondros\Api\Model\Entity\Organization $child
     * @return Organization
     */
    public function addAnswer(\Hondros\Api\Model\Entity\Organization $child)
    {
        $this->children[] = $child;
    
        return $this;
    }
    
    /**
     * Remove child
     *
     * @param \Hondros\Api\Model\Entity\Organization $child
     */
    public function removeChild(\Hondros\Api\Model\Entity\Organization $child)
    {
        $this->children->removeElement($child);
    }
    
    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }
}
