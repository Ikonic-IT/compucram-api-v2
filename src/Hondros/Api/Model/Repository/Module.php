<?php

namespace Hondros\Api\Model\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query;
use Monolog\Logger;
use Predis\Client as Redis;
use Hondros\Api\Model\Repository\RepositoryAbstract;
use Laminas\Stdlib\Hydrator;
use Laminas\Config\Config;
use InvalidArgumentException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Hondros\Common\Collection;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Query\ResultSetMapping;

class Module extends RepositoryAbstract
{
    /**
    * @var \Monolog\Logger
    */
    protected $logger;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Module constructor.
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     * @param Logger $logger
     * @param Redis $redis
     * @param Config $config
     */
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class, Logger $logger, Redis $redis,
                                Config $config)
    {
        $this->logger = $logger;
        $this->redis = $redis;
        $this->config = $config;
        
        parent::__construct($em, $class);
    } 
    
    /**
     * Finds all modules for an exam
     *
     * @param int $examId
     * @return array
     */
    public function findForExam($examId, $params = [])
    {
        $dql = "
        SELECT m
        FROM Hondros\Api\Model\Entity\Module m
        JOIN m.examModule em
        WHERE em.exam = {$examId} ";
    
        $query = $this->getEntityManager()->createQuery($dql);
    
        return new Paginator($query, false);
    }
}