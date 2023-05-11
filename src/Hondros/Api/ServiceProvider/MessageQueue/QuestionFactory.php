<?php

namespace Hondros\Api\ServiceProvider\MessageQueue;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\MessageQueue;

class QuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\MessageQueue\Progress
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new MessageQueue\Question(
            $container->get('config'),
            $container->get('redis'),
            $container->get('messageQueue'),
            $container->get('entityManager')
        );
    }
}