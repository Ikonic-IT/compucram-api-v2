<?php

namespace Hondros\Test;

use Doctrine\ORM\EntityManager;

/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 4/3/17
 * Time: 3:44 PM
 */
abstract class FunctionalAbstract extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityManager null
     */
    protected $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * make sure we clean up doctrine or we'll have odd things happen
     */
    protected function tearDown(): void
    {
        // clear up uof instead of closing/resetting the manager since that causes "EntityManager is closed"
        $this->getEntityManager()->getUnitOfWork()->clear();

        parent::tearDown();
    }

    /**
     * @return \Laminas\ServiceManager\ServiceManager
     */
    protected function getServiceManager()
    {
        return Bootstrap::getServiceManager();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (empty($this->entityManager)) {
            $this->entityManager = $this->getServiceManager()->get('entityManager');
        }

        return $this->entityManager;
    }
}