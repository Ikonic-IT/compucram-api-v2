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

class QuestionAudit extends RepositoryAbstract
{   
    /**
     * @var boolean if true, caching of this entity will be enabled
     */
    const CACHE_ENABLED = false;
    
    /**
     * @var int time in seconds for cache to expire
     */
    const CACHE_TTL = 28800; // 8 hours
    
    /**
     * @var string cache key to identify this repo's caches
     */
    const CACHE_ID = 'questionaudit:';
    
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
}
