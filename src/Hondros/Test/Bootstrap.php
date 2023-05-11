<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 4/3/17
 * Time: 3:54 PM
 */

namespace Hondros\Test;

use Laminas\ServiceManager\ServiceManager;

/**
 * Class Bootstrap
 *
 * currently using this as a way to bring in the service manager, look at a better solution
 *
 * @package Hondros\Test
 */
class Bootstrap
{
    /**
     * @var ServiceManager
     */
    protected static $serviceManager;

    /**
     * @param $serviceManager
     */
    public static function setServiceManager($serviceManager)
    {
        self::$serviceManager = $serviceManager;
    }

    /**
     * @return ServiceManager
     */
    public static function getServiceManager()
    {
        return self::$serviceManager;
    }

    /**
     * clean things up
     * @todo clean up rabbitmq
     */
    public static function cleanUp()
    {
        $validDbName = substr(self::getServiceManager()->get('config')->doctrine->params->dbname, -8) ===
            '_phpunit';
        if (!defined('PHPUNIT') || !PHPUNIT || !$validDbName) {
            throw new \Exception("This can only run in the functional test DB.");
        }

        $entitiesToRemove = [
            'Hondros\Api\Model\Entity\Organization',
            'Hondros\Api\Model\Entity\Exam',
            'Hondros\Api\Model\Entity\Module',
            'Hondros\Api\Model\Entity\User',
            'Hondros\Api\Model\Entity\Industry',
            'Hondros\Api\Model\Entity\State',
            'Hondros\Api\Model\Entity\QuestionBank',
            'Hondros\Api\Model\Entity\Question',
            'Hondros\Api\Model\Entity\User',
        ];

        foreach ($entitiesToRemove as $entity) {
            self::getServiceManager()->get('entityManager')->createQuery("DELETE {$entity}")->execute();
        }

        self::getServiceManager()->get('redis')->flushall();
    }
}