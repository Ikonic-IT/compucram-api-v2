<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class Import implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Import constructor.
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
        
        $controllers->post('/import/exams', function () {
            return JsonResponse::create($this->serviceManager->get('moduleImporter')->importFiles('exams', $_FILES));
        });
        
        $controllers->post('/import/modules', function () {
            return JsonResponse::create($this->serviceManager->get('moduleImporter')->importFiles('modules', $_FILES));
        });

        $controllers->post('/import/users', function () {
            return JsonResponse::create($this->serviceManager->get('userImporter')->importDefaults());
        });
        
        return $controllers;
    }
}