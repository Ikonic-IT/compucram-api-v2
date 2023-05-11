<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class UserFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\User
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\User(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('userRepository'),
            $serviceLocator->get('config'),
            $serviceLocator->get('redis'),
            $serviceLocator->get('user')
        );
    }
}