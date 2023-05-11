<?php

namespace Hondros\Api\ServiceProvider\EntityListener;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Listener;

class AnswerFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Listener\Answer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Listener\Answer(
            $serviceLocator->get('config'),
            $serviceLocator->get('redis'),
            $serviceLocator->get('answerHydratorStrategy'),
            $serviceLocator
        );
    }
}