<?php

namespace Hondros\Api\ServiceProvider\EntityListener;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Listener;

class QuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Listener\Question
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Listener\Question(
            $serviceLocator->get('config'),
            $serviceLocator->get('redis'),
            $serviceLocator->get('questionHydratorStrategy'),
            $serviceLocator
        );
    }
}