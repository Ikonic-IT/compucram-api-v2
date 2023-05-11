<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class Report implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Answer constructor.
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

        $controllers->get('/reports/stats', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'GET.REPORT.APPLICATION.STATS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('reportService')->getApplicationStats());
        });

        $controllers->get('/reports/questions/stats', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'GET.REPORT.QUESTION.STATS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('reportService')->getQuestionStats());
        });
            
        return $controllers;
    }
}