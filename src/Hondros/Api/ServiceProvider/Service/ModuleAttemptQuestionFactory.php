<?php

namespace Hondros\Api\ServiceProvider\Service;

//use Laminas\ServiceManager\FactoryInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;


class ModuleAttemptQuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\ModuleAttemptQuestion
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Service\ModuleAttemptQuestion(
            $container->get('entityManager'),
            $container->get('logger'),
            $container->get('moduleAttemptQuestionRepository'),
            $container->get('progressQuestionRepository')
        );
    }
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}
