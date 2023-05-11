<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class AssessmentAttemptFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\AssessmentAttempt
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\AssessmentAttempt(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('assessmentAttemptRepository'),
            $serviceLocator->get('enrollmentRepository'),
            $serviceLocator->get('examModuleRepository'),
            $serviceLocator->get('questionRepository'),
            $serviceLocator->get('progressRepository')
            
        );
    }
}