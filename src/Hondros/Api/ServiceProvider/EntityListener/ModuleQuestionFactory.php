<?php

namespace Hondros\Api\ServiceProvider\EntityListener;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Listener;

class ModuleQuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Listener\ModuleQuestion
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Listener\ModuleQuestion(
            $container->get('config'),
            $container->get('redis'),
            $container->get('questionHydratorStrategy'),
            $container
        );
    }
}