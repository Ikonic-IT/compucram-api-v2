<?php

/**
 * the bad, data providers run before any test which means all the data gets populated in the DB for all tests, then the
 * actual tests run. The issue is we wipe out the database after each tests, the data required by the data providers is
 * deleted and the tests will fail. We'd have to stop using data providers to use dbunit with fixture files which
 * sucks too or just make sure we are careful so each test only uses the data it needs by creating fresh new data
 * for itself.
 */

error_reporting(E_ALL);
define('PHPUNIT', true);

$app = require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

$email = 'test' . uniqid() . '@user.com';
$authUser = new \Hondros\Api\Model\Entity\User();
$authUser
    ->setFirstName('Test')
    ->setLastName('User')
    ->setEmail($email)
    ->setPassword('temppassword')
    ->setRole(\Hondros\Api\Model\Entity\User::ROLE_ADMIN)
    ->setToken($authUser->generateToken())
    ->setStatus(\Hondros\Api\Model\Entity\User::STATUS_ACTIVE);

$em = $serviceManager->get('entityManager');
$em->persist($authUser);
$em->flush();

// set test user
$serviceManager
    ->setAllowOverride(true)
    ->setService('user', $authUser)
    ->setAllowOverride(false);

\Hondros\Test\Bootstrap::setServiceManager($serviceManager);

// when we are all done, lets clean up
register_shutdown_function(function(){
    \Hondros\Test\Bootstrap::cleanUp();
});