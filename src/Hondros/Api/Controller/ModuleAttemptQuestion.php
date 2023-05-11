<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class ModuleAttemptQuestion implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * ModuleAttemptQuestion constructor.
     * @param $serviceManager
     */
    public function __construct($serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * @param Application $app
     * @return mixed
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/module-attempt-questions/{moduleAttemptQuestionId}', function ($moduleAttemptQuestionId) {
            return JsonResponse::create($this->serviceManager->get('moduleAttemptQuestionService')->find($moduleAttemptQuestionId, $_GET));
        });

        return $controllers;
    }
}