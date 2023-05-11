<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class ModuleFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Module
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\Module(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('moduleRepository'),
            $serviceLocator->get('questionRepository')
            
        );
    }
}