<?php

namespace Hondros\Api\ServiceProvider\Adapter;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

use Predis\Client;

class RedisFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $options = $serviceLocator->get('config')->redis;
        return new Client((array)$options->toArray(), ['prefix' => $serviceLocator->get('config')->redis->prefix]);
    }
}