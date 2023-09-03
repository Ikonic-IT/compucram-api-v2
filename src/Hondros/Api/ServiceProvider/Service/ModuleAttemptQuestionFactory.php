<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class ModuleAttemptQuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\ModuleAttemptQuestion
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\ModuleAttemptQuestion(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('moduleAttemptQuestionRepository'),
            $serviceLocator->get('progressQuestionRepository')
        );
    }
}