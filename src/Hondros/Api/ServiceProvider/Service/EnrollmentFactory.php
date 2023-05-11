<?php

namespace Hondros\Api\ServiceProvider\Service;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;

class EnrollmentFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Enrollment
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Service\Enrollment(
            $serviceLocator->get('entityManager'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('enrollmentRepository'),
            $serviceLocator->get('config'),
            $serviceLocator->get('examRepository'),
            $serviceLocator->get('moduleRepository'),
            $serviceLocator->get('questionRepository'),
            $serviceLocator->get('userRepository'),
            $serviceLocator->get('organizationRepository'),
            $serviceLocator->get('mailChimpClient')
        );
    }
}