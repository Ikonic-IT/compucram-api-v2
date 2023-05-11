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
use Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy;
use Hondros\Api\Model\Entity;

class User extends RepositoryAbstract
{
    /**
     * @var boolean if true, caching of this entity will be enabled
     */
    const CACHE_ENABLED = true;
    
    /**
     * @var int time in seconds for cache to expire
     */
    const CACHE_TTL = 28800; // 8 hours
    
    /**
     * @var string cache key to identify this repo's caches
     */
    const CACHE_ID = 'user:';

    /**
     * @var int time in seconds for cache to expire
     */
    const CACHE_TTL_TOKEN_HASH = 86400; // 1 day

    /**
     * @var string to track the different token hashmaps
     */
    const CACHE_ID_TOKEN_HASH = 'hash:token:';
    
    /**
    * @var Monolog\Logger
    */
    protected $logger;
    
    protected $redis;
    
    protected $config;

    /**
     * @var \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\User
     */
    protected $userHydratorStrategy;
    
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class, Logger $logger, Redis $redis,
        Config $config, Strategy\Entity\User $userHydratorStrategy)
    {
        $this->logger = $logger;
        $this->redis = $redis;
        $this->config = $config;
        $this->userHydratorStrategy = $userHydratorStrategy;
        
        parent::__construct($em, $class);
    }

    /**
     * cache users and track token ids
     *
     * @param string $token
     * @return mixed
     */
    public function findOneByToken($token)
    {
        // do we already have this token
        $tokenCacheId = self::CACHE_ID_TOKEN_HASH . substr($token, 0, 1);
        $tokenData = $this->redis->hget($tokenCacheId, $token);

        // check cache
        if ($tokenData && $data = $this->getCacheAdapter()->get(self::CACHE_ID . $tokenData)) {
            $hydrator = $this->userHydratorStrategy->getHydrator();
            return $hydrator->hydrate(json_decode($data, true), new Entity\User());
        }

        return parent::findOneByToken($token);
    }
}
