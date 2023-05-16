<?php

namespace Hondros\Api\ServiceProvider\Repository;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Repository;
use Interop\Container\ContainerInterface;


class AssessmentAttemptFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Repository\AssessmentAttempt
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $em = $container->get('entityManager');
        
        return new Repository\AssessmentAttempt(
            $em,
            $em->getClassMetadata('Hondros\Api\Model\Entity\AssessmentAttempt'),
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