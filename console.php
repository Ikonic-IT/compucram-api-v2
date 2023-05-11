<?php

set_time_limit(0);

$app = require_once('bootstrap.php');

use Hondros\Api\Console;

/** @var \Knp\Console\Application $application */
$application = $app['console'];
$application->add((new Console\Job\Content\ImportModule())->setServiceManager($serviceManager));
$application->add((new Console\Job\Content\ImportExam())->setServiceManager($serviceManager));
$application->add((new Console\Job\Content\UpdateModule())->setServiceManager($serviceManager));
$application->add((new Console\Job\Content\DeleteEnrollments())->setServiceManager($serviceManager));

$application->add((new Console\Job\Progress\Recalculate())->setServiceManager($serviceManager));
$application->add((new Console\Job\Progress\AddQuestion())->setServiceManager($serviceManager));

$application->add((new Console\Job\ModuleAttempt\Recalculate())->setServiceManager($serviceManager));

$application->add((new Console\Job\ModuleQuestion\AddedNew())->setServiceManager($serviceManager));

$application->add((new Console\Job\Question\StatusChange())->setServiceManager($serviceManager));
$application->add((new Console\Job\Question\AddToElasticsearch())->setServiceManager($serviceManager));
$application->add((new Console\Job\Question\AddAllToElasticsearch())->setServiceManager($serviceManager));

$application->add((new Console\Job\RunOnce\MigrateQuestionBankToModuleQuestion())->setServiceManager($serviceManager));
$application->add((new Console\Job\RunOnce\AddEnrollmentsBasedOnExams())->setServiceManager($serviceManager));
$application->add((new Console\Job\RunOnce\EmailStudentsNewExam())->setServiceManager($serviceManager));

$application->add((new Console\Job\Report\QuestionStats())->setServiceManager($serviceManager));
$application->add((new Console\Job\Report\TotalStats())->setServiceManager($serviceManager));

$application->run();