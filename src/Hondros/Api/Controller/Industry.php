<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class Industry implements ControllerProviderInterface
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
        $controllers->get('/industries', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'GET.INDUSTRIES')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('industryService')->findAll($_GET));
        });
        
        return $controllers;
    }
}