<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class AnswerFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Answer
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Service\Answer(
            $container->get('entityManager'),
            $container->get('logger'),
            $container->get('answerRepository'),
            $container->get('answerAuditRepository')
        );
    }
}