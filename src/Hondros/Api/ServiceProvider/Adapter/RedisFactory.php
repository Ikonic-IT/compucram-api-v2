<?php

namespace Hondros\Api\ServiceProvider\Adapter;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;

use Predis\Client;

class RedisFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $options = $container->get('config')->redis;
        return new Client((array)$options->toArray(), ['prefix' => $container->get('config')->redis->prefix]);
    }
}