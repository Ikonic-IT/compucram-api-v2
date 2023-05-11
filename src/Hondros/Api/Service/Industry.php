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

class Industry extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\Industry';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'Industry';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\Industry
     */
    protected $repository;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\Industry $repository)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
    }
}