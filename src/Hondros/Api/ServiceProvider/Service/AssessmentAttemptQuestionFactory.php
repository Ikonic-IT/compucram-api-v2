<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class AssessmentAttemptQuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\AssessmentAttemptQuestion
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\AssessmentAttemptQuestion(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('assessmentAttemptQuestionRepository'),
            $serviceLocator->get('assessmentAttemptRepository')
        );
    }
}