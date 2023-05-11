<?php

namespace Hondros\Api\ServiceProvider\Util\Excel;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Util\Excel;

class ValidatorFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Util\ContentImporter
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Excel\Validator(
            $serviceLocator->get('industryRepository'),
            $serviceLocator->get('stateRepository'),
            $serviceLocator->get('moduleRepository')
        );
    }
}