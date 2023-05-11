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

class ModuleQuestion extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\ModuleQuestion';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'ModuleQuestion';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\ModuleQuestion
     */
    protected $repository;

    /**
     * Module constructor.
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param Repository\ModuleQuestion $repository
     */
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\ModuleQuestion $repository)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
    }
}