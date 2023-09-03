<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class ProgressQuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\ProgressQuestion
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\ProgressQuestion(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('progressQuestionRepository'),
            $serviceLocator->get('moduleAttemptQuestionRepository'),
            $serviceLocator->get('moduleRepository'),
            $serviceLocator->get('progressRepository'),
            $serviceLocator->get('questionRepository'),
            $serviceLocator->get('moduleAttemptRepository')
        );
    }
}