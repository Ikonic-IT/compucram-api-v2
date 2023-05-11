<?php
namespace Hondros\Api\MessageQueue;

use Laminas\Config\Config as LaminasConfig;
use Predis\Client as Redis;
use PhpAmqpLib\Connection\AMQPStreamConnection;

abstract class JobAbstract
{
    protected $config;
    protected $connection;
    protected $cacheAdapter;
    protected $messageQueue;
    protected $queue = 'audio';
    
    public function __construct(LaminasConfig $config, Redis $cacheAdapter, AMQPStreamConnection $messageQueue)
    {
        // store config object
        $this->config = $config;

        // store redis adapter
        $this->cacheAdapter = $cacheAdapter;

        $this->messageQueue = $messageQueue;
    }
    
//    public function __destruct()
//    {
//        if (!empty($this->connection)) {
//            $this->messageQueue->close();
//        }
//    }
//
    public function getConn()
    {
        return $this->messageQueue;
    }

    public function getCacheAdapter()
    {
        return $this->redis;
    }
    
    public function getConfig()
    {
        return $this->config;
    }
}