<?php

namespace Hondros\Api\ServiceProvider\Adapter;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MonologFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $logConfig = $serviceLocator->get('config')->log;
        $logger = new Logger('logger');
        $logger->pushHandler(new StreamHandler($logConfig->file->path));
        
        return $logger;
    }
}
