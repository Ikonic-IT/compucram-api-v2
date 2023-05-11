<?php

namespace Hondros\Api\Model\Repository;

use Monolog\Logger;
use Predis\Client as Redis;
use Laminas\Config\Config;

class ModuleQuestion extends RepositoryAbstract
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
     * ModuleQuestion constructor.
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
}
