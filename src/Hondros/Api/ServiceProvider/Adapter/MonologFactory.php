<?php

namespace Hondros\Api\ServiceProvider\Adapter;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MonologFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $logConfig = $container->get('config')->log;
        $logger = new Logger('logger');
        $logger->pushHandler(new StreamHandler($logConfig->file->path));
        
        return $logger;
    }
}
