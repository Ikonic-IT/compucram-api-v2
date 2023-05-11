<?php

namespace Hondros\Api\ServiceProvider\Repository;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Repository;

class IndustryFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Repository\Industry
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $em = $serviceLocator->get('entityManager');
        
        return new Repository\Industry(
            $em,
            $em->getClassMetadata('Hondros\Api\Model\Entity\Industry'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('redis'),
            $serviceLocator->get('config')
        );
    }
}