<?php

namespace Hondros\Api\ServiceProvider\Service\Export;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class ContentFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Export\Content
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\Export\Content(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('examRepository'),
            $serviceLocator->get('examModuleRepository'),
            $serviceLocator->get('examToExcel'),
            $serviceLocator->get('questionRepository'),
            $serviceLocator->get('config'),
            $serviceLocator->get('s3Client')
        );
    }
}