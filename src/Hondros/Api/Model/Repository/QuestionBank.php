<?php

namespace Hondros\Api\Model\Repository;

use Predis\Client as Redis;
use Monolog\Logger;
use Laminas\Config\Config;

class QuestionBank extends RepositoryAbstract
{
    /**
    * @var \Monolog\Logger
    */
    protected $logger;

    /**
     * @var Redis
     */
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
