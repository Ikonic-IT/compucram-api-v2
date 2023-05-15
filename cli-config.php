<?php

chdir(__DIR__);

$loader = require_once "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

$serviceManager = new \Laminas\ServiceManager\ServiceManager(require 'config/servicemanager.php');
$serviceManager->setService('config', \Hondros\Api\Util\Config::init());

use Doctrine\ORM\Tools\Console\ConsoleRunner;
return ConsoleRunner::createHelperSet($serviceManager->get('entityManager'));
