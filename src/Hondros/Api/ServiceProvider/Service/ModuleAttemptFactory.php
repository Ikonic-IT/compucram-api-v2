<?php

namespace Hondros\Api\ServiceProvider\Service;

//use Laminas\ServiceManager\FactoryInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ModuleAttemptFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\ModuleAttempt
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Service\ModuleAttempt(
            $container->get('entityManager'),
            $container->get('logger'),
            $container->get('moduleAttemptRepository'),
            $container->get('enrollmentRepository'),
            $container->get('moduleRepository'),
            $container->get('questionRepository'),
            $container->get('progressRepository'),
            $container->get('progressQuestionRepository'),
			$container->get('moduleAttemptQuestionRepository'),
			$container->get('progressService')
        );
    }
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}