<?php

namespace Hondros\Api\ServiceProvider\Util;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Util;

class ContentImporterFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Util\ContentImporter
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Util\ContentImporter(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('moduleRepository'),
            $serviceLocator->get('industryRepository'),
            $serviceLocator->get('config'),
            $serviceLocator->get('examRepository'),
            $serviceLocator->get('stateRepository'),
            $serviceLocator->get('questionMessageQueue'),
            $serviceLocator->get('questionRepository'),
            $serviceLocator->get('excelValidator')
        );
    }
}