<?php

namespace Hondros\Api\ServiceProvider\EntityListener;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Listener;

class AnswerFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Listener\Answer
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Listener\Answer(
            $container->get('config'),
            $container->get('redis'),
            $container->get('answerHydratorStrategy'),
            $container
        );
    }
}