<?php

namespace Hondros\Api\ServiceProvider\Service;

//use Laminas\ServiceManager\FactoryInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ProgressQuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\ProgressQuestion
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Service\ProgressQuestion(
            $container->get('entityManager'),
            $container->get('logger'),
            $container->get('progressQuestionRepository'),
            $container->get('moduleAttemptQuestionRepository'),
            $container->get('moduleRepository'),
            $container->get('progressRepository'),
            $container->get('questionRepository'),
            $container->get('moduleAttemptRepository')
        );
    }
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}