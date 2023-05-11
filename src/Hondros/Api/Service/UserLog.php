<?php

namespace Hondros\Api\Service;

use Hondros\Api\Service\ServiceAbstract;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Doctrine\ORM\EntityManager;
use Laminas\Config\Config;
use InvalidArgumentException;
use DateTime;

class UserLog extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\UserLog';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'UserLog';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\UserLog
     */
    protected $repository;
    
    /**
     * @var \Hondros\Api\Model\Repository\User
     */
    protected $userRepository;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\UserLog $repository,
        Repository\User $userRepository) 
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->userRepository = $userRepository;
    }
    
    public function save($params)
    {
        // create new
        if (empty($params['id'])) {
            return $this->createNew($params);
        }
        
        throw new InvalidArgumentException("Invalid method called", 400);
    }
    
    /**
     * @todo find user before creating after we add caching - reference for now for performance
     * @param array $params
     * @throws InvalidArgumentException
     * @return \Hondros\Common\DoctrineSingle
     */
    protected function createNew($params)
    {
        unset($params['user']);
        
        // validate the user
        if (empty($params['userId']) || filter_var($params['userId'], FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid user id passed {$params['userId']}", 400);
        }
        
        // enable this once we add caching to find
        // $user = $this->userRepository->find($params['userId']);
        
        $userLog = new Entity\UserLog();
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\UserLog();
        $hydrator = $strategy->getHydrator();
        $hydrator->hydrate($params, $userLog);
        
        // track now
        $userLog->setCreated(new \DateTime());
        
        // add user
        //$userLog->setUser($user);
        // for now add reference
        $userLog->setUser($this->entityManager->getReference('Hondros\Api\Model\Entity\User', $params['userId']));
        
        $this->entityManager->persist($userLog);
        $this->entityManager->flush();
        
        return new DoctrineSingle($userLog, self::ENTITY_STRATEGY);
    }
}