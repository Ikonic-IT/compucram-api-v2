<?php

namespace Hondros\Api\Service;

use Hondros\Api\Service\ServiceAbstract;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use DateTime;

class Organization extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\Organization';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'Organization';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\Organization
     */
    protected $repository;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\Organization $repository) 
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
    }
    
    public function save($params)
    {
        // create new
        if (empty($params['id'])) {
            return $this->createNew($params);
        }
    }

    public function update($id, $params)
    {
        if (empty($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid id {$id}", 400);
        }

        // get user
        $organization = $this->repository->find($id);

        // hydrate new data
        $hydrator = (new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Organization())
            ->getHydrator();
        $hydrator->hydrate($params, $organization);
        $organization->setModified(new \DateTime());

        // save
        $this->entityManager->flush();

        // done
        return new DoctrineSingle($organization, self::ENTITY_STRATEGY);
    }
    
    protected function createNew($params)
    {
        $date = new DateTime();
    
        // validate
        if (empty($params['name'])) {
            throw new InvalidArgumentException("Must supply a valid name.", 400);
        }
    
        // already in the system? - probably should use a code instead of name
        $organization = $this->repository->findByName($params['name']);
        if (!empty($organization)) {
            throw new InvalidArgumentException("An organization by the name {$params['name']} already exists", 400);
        }
    
        // was a parent passed?
        $parent = null;
        $parentId = null;
        if (!empty($params['parentId'])) {
            // see if valid
            if (filter_var($params['parentId'], FILTER_VALIDATE_INT) === false) {
                throw new InvalidArgumentException("The following parentId {$params['parentId']} is not valid.", 400);
            }
            
            $parentId = (int) trim($params['parentId']);
            $parent = $this->repository->find($parentId);
            if (empty($parent)) {
                throw new InvalidArgumentException("The parent {$params['parentId']} was not found.", 400);
            }
            
            // set a business logic rule to not allow more than one level deep
            if (!empty($parent->getParentId())) {
                throw new InvalidArgumentException("Can't nest lower than one level, this parent already has a parent.", 400);
            }
        }
        
        // create org
        $organization = (new Entity\Organization())
            ->setParent($parent)
            ->setParentId($parentId)
            ->setName($params['name'])
            ->setCreated($date);
        
        if (!empty($params['url'])) {
            if (filter_var($params['url'], FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException("The following url {$params['url']} is not valid.", 400);
            }
            $organization->setUrl($params['url']);
        }
        
        if (!empty($params['redirectUrl'])) {
            if (filter_var($params['redirectUrl'], FILTER_VALIDATE_URL) === false) {
                throw new InvalidArgumentException("The following redirect url {$params['redirectUrl']} is not valid.", 400);
            }
            $organization->setRedirectUrl($params['redirectUrl']);
        }
    
        if (!empty($params['credits'])) {
            $organization->setCredits($params['credits']);
        }
        
        $this->entityManager->persist($organization);
         
        // save
        $this->entityManager->flush();
    
        // return the module attempt info, with questions and answers
        return new DoctrineSingle($organization, self::ENTITY_STRATEGY);
    }
}