<?php

namespace Hondros\Api\ServiceProvider\Service;

// use Laminas\ServiceManager\FactoryInterface;
// use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Service;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class EnrollmentFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Service\Enrollment
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Service\Enrollment(
            $container->get('entityManager'),
            $container->get('logger'),
            $container->get('enrollmentRepository'),
            $container->get('config'),
            $container->get('examRepository'),
            $container->get('moduleRepository'),
            $container->get('questionRepository'),
            $container->get('userRepository'),
            $container->get('organizationRepository'),
            $container->get('mailChimpClient')
        );
    }
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}