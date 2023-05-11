<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class OrganizationFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Organization
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\Organization(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('organizationRepository')
        );
    }
}