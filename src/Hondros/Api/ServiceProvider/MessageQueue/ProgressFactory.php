<?php

namespace Hondros\Api\ServiceProvider\MessageQueue;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\MessageQueue;

class ProgressFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\MessageQueue\Progress
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new MessageQueue\Progress(
            $serviceLocator->get('config'),
            $serviceLocator->get('redis'),
            $serviceLocator->get('messageQueue'),
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('progressService'),
			$serviceLocator->get('moduleAttemptService'),
			$serviceLocator->get('progressRepository'),
            $serviceLocator->get('progressQuestionRepository')
        );
    }
}