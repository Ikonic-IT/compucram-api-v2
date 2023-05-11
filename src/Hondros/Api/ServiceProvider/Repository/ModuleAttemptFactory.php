<?php

namespace Hondros\Api\ServiceProvider\Repository;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Repository;

class ModuleAttemptFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Repository\ModuleAttempt
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $em = $serviceLocator->get('entityManager');
        
        return new Repository\ModuleAttempt(
            $em,
            $em->getClassMetadata('Hondros\Api\Model\Entity\ModuleAttempt'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('redis'),
            $serviceLocator->get('config')
        );
    }
}