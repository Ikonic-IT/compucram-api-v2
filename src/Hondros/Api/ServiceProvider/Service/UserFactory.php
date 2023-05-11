<?php

namespace Hondros\Api\ServiceProvider\Service;

// use Laminas\ServiceManager\FactoryInterface;
// use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
class UserFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\User
     */
    // public function createService(ServiceLocatorInterface $serviceLocator)
    // {
    //     return new Service\User(
    //         $serviceLocator->get('entityManager'),
    //         $serviceLocator->get('logger'),
    //         $serviceLocator->get('userRepository'),
    //         $serviceLocator->get('config'),
    //         $serviceLocator->get('redis'),
    //         $serviceLocator->get('user')
    //     );
    // }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        
        new Service\User(
            $container->get('entityManager'),
            $container->get('logger'),
            $container->get('userRepository'),
            $container->get('config'),
            $container->get('redis'),
            $container->get('user')
        );
    }
}