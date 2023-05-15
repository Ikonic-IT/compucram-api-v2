<?php

namespace Hondros\Api\ServiceProvider\Repository;

//use Laminas\ServiceManager\FactoryInterface;
//use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Repository;

use Hondros\Api\Service;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class QuestionFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Repository\Question
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $em = $container->get('entityManager');
        
        return new Repository\Question(
            $em,
            $em->getClassMetadata('Hondros\Api\Model\Entity\Question'),
            $container->get('logger'),
            $container->get('redis'),
            $container->get('config'),
            $container->get('questionHydratorStrategy'),
            $container->get('answerHydratorStrategy'),
            $container->get('questionBankRepository')
        );
    }
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services);
    }
}