<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class Organization implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Organization constructor.
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

        // admin
        $controllers->get('/organizations', function () {
            return JsonResponse::create($this->serviceManager->get('organizationService')->findAll($_GET));
        });

        // admin
        $controllers->post('/organizations', function () {
            return JsonResponse::create($this->serviceManager->get('organizationService')->save($_POST));
        });

        $controllers->get('/organizations/{organizationId}', function ($organizationId) {
            return JsonResponse::create($this->serviceManager->get('organizationService')->find($organizationId, $_GET));
        });

        // admin
        $controllers->put('/organizations/{organizationId}', function ($organizationId) {
            return JsonResponse::create($this->serviceManager->get('organizationService')->update($organizationId, $_POST));
        });
        
        return $controllers;
    }
}