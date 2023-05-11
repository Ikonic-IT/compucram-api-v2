<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 4/11/17
 * Time: 1:19 PM
 */

namespace Hondros\Api\Model\Listener;

use Laminas\ServiceManager\ServiceLocatorInterface;

abstract class ListenerAbstract
{
    /**
     * @return bool
     */
    abstract protected function isEnabled();
}