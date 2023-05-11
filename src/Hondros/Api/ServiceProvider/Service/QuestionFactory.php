<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class QuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Question
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\Question(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('questionRepository'),
            $serviceLocator->get('moduleRepository'),
            $serviceLocator->get('elasticsearch'),
            $serviceLocator->get('questionAuditRepository'),
            $serviceLocator->get('questionBankRepository')
        );
    }
}