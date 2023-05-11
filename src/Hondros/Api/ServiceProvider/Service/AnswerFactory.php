<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class AnswerFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Answer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\Answer(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('answerRepository'),
            $serviceLocator->get('answerAuditRepository')
        );
    }
}