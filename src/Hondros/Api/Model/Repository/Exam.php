<?php

namespace Hondros\Api\Model\Repository;

use Monolog\Logger;
use Predis\Client as Redis;
use Hondros\Api\Model\Repository\RepositoryAbstract;
use Laminas\Config\Config;

class Exam extends RepositoryAbstract
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
    const CACHE_ID = 'exam:';
    
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
    
    public function findOneByCode($code)
    {   
        $dql = "
            SELECT e
            FROM {$this->_entityName} e
            WHERE e.code = :code";
        
        $cacheId = self::CACHE_ID . ':code:' . $code;
        $query = $this->getEntityManager()->createQuery($dql)
            ->setParameter('code', $code);
        
        // check for cache
        if (self::CACHE_ENABLED) {
            $query->useResultCache(self::CACHE_ENABLED, self::CACHE_TTL, $cacheId);
        }   
        
        $results = $query->getResult();
        
        if (empty($results)) {
            return false;
        }
        
        return $results[0];
    }

   
}
