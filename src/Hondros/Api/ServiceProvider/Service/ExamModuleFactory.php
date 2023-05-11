<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class ExamModuleFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\ExamModule
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\ExamModule(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('examModuleRepository'),
            $serviceLocator->get('examRepository'),
            $serviceLocator->get('moduleRepository')
        );
    }
}