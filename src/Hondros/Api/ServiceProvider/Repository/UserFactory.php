<?php

namespace Hondros\Api\ServiceProvider\Repository;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Repository;
use Interop\Container\ContainerInterface;

class UserFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Repository\User
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $em = $container->get('entityManager');
        
        return new Repository\User(
            $em,
            $em->getClassMetadata('Hondros\Api\Model\Entity\User'),
            $container->get('logger'),
            $container->get('redis'),
            $container->get('config'),
            $container->get('userHydratorStrategy')
        );
    }

    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}