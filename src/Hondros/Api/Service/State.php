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

class State extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\State';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'State';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\State
     */
    protected $repository;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\State $repository)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
    }
}