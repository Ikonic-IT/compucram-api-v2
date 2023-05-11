<?php

namespace Hondros\Api\ServiceProvider\MessageQueue;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\MessageQueue;

class AudioFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\MessageQueue\Audo
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new MessageQueue\Audio(
            $serviceLocator->get('config'),
            $serviceLocator->get('redis'),
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('questionRepository'),
            $serviceLocator->get('answerRepository')
        );
    }
}