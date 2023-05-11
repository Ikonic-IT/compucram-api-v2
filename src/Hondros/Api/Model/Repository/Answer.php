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
use Hondros\Common\Collection;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Query\ResultSetMapping;

class Answer extends RepositoryAbstract
{
    const CACHE_ID = 'entity:answer:';

    /**
    * @var Monolog\Logger
    */
    protected $logger;
    
    protected $redis;
    
    protected $config;
    
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class, Logger $logger, Redis $redis, Config $config) 
    {
        $this->logger = $logger;
        $this->redis = $redis;
        $this->config = $config;
        
        parent::__construct($em, $class);
    }
    
    public function findForStudyWithoutAudio($offset = 0)
    {
        $dql = "
        SELECT a
        FROM {$this->_entityName} a
        WHERE a.audioHash IS NULL AND
            qb.type = 'study'
        ";
    
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setFirstResult($offset)
            ->setMaxResults(500);
    
        return $query->getResult();
    }
}
