<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class IndustryFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Industry
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\Industry(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('industryRepository')
        );
    }
}