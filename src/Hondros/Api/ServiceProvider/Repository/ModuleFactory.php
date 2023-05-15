<?php

namespace Hondros\Api\ServiceProvider\Repository;

//use Laminas\ServiceManager\FactoryInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;

use Hondros\Api\Service;
use Hondros\Api\Model\Repository;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ModuleFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Repository\Module
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $em = $container->get('entityManager');
        
        return new Repository\Module(
            $em,
            $em->getClassMetadata('Hondros\Api\Model\Entity\Module'),
            $container->get('logger'),
            $container->get('redis'),
            $container->get('config')
        );
    }
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}