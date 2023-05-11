<?php

namespace Hondros\Api\ServiceProvider\Util;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Util;

class UserImporterFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Util\UserImporter
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Util\UserImporter(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
			$serviceLocator->get('userService'),
            $serviceLocator->get('enrollmentService'),
            $serviceLocator->get('config')
        );
    }
}