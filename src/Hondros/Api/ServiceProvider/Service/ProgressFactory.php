<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class ProgressFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Progress
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\Progress(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('progressRepository'),
			$serviceLocator->get('progressQuestionRepository'),
			$serviceLocator->get('moduleAttemptRepository'),
			$serviceLocator->get('assessmentAttemptRepository'),
			$serviceLocator->get('assessmentAttemptQuestionRepository'),
            $serviceLocator->get('enrollmentRepository'),
            $serviceLocator->get('examModuleRepository')
        );
    }
}