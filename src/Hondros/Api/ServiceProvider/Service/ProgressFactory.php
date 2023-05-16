<?php

namespace Hondros\Api\ServiceProvider\Service;

//use Laminas\ServiceManager\FactoryInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ProgressFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Progress
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Service\Progress(
            $container->get('entityManager'),
            $container->get('logger'),
            $container->get('progressRepository'),
			$container->get('progressQuestionRepository'),
			$container->get('moduleAttemptRepository'),
			$container->get('assessmentAttemptRepository'),
			$container->get('assessmentAttemptQuestionRepository'),
            $container->get('enrollmentRepository'),
            $container->get('examModuleRepository')
        );
    }
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}