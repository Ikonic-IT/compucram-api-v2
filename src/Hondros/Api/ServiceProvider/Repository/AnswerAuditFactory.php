<?php

namespace Hondros\Api\ServiceProvider\Repository;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Hondros\Api\Model\Repository;

class AnswerAuditFactory implements FactoryInterface
{
    /**
     * used to track all dependencies
     * 
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Hondros\Api\Model\Repository\AnswerAudit
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $em = $serviceLocator->get('entityManager');
        
        return new Repository\AnswerAudit(
            $em,
            $em->getClassMetadata('Hondros\Api\Model\Entity\AnswerAudit'),
            $serviceLocator->get('logger'),
            $serviceLocator->get('redis'),
            $serviceLocator->get('config')
        );
    }
}