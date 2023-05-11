<?php

namespace Hondros\Api\ServiceProvider\EntityListener;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Listener;

class UserFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     *
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Listener\User
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Listener\User(
            $serviceLocator->get('config'),
            $serviceLocator->get('redis'),
            $serviceLocator->get('userHydratorStrategy')
        );
    }
}