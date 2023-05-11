<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class ModuleAttemptFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\ModuleAttempt
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\ModuleAttempt(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('moduleAttemptRepository'),
            $serviceLocator->get('enrollmentRepository'),
            $serviceLocator->get('moduleRepository'),
            $serviceLocator->get('questionRepository'),
            $serviceLocator->get('progressRepository'),
            $serviceLocator->get('progressQuestionRepository'),
			$serviceLocator->get('moduleAttemptQuestionRepository'),
			$serviceLocator->get('progressService')
        );
    }
}