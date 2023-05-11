<?php

chdir(__DIR__);

$loader = require_once "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

$serviceManager = new \Zend\ServiceManager\ServiceManager(new \Zend\ServiceManager\Config(require 'config/servicemanager.php'));
$serviceManager->setService('config', \Hondros\Api\Util\Config::init());

use Doctrine\ORM\Tools\Console\ConsoleRunner;
return ConsoleRunner::createHelperSet($serviceManager->get('entityManager'));
