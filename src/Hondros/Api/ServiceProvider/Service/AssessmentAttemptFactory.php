<?php

namespace Hondros\Api\ServiceProvider\Service;

//use Laminas\ServiceManager\FactoryInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class AssessmentAttemptFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\AssessmentAttempt
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Service\AssessmentAttempt(
            $container->get('entityManager'),
            $container->get('logger'),
            $container->get('assessmentAttemptRepository'),
            $container->get('enrollmentRepository'),
            $container->get('examModuleRepository'),
            $container->get('questionRepository'),
            $container->get('progressRepository')
            
        );
    }
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}