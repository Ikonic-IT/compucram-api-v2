<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class Module implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Module constructor.
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
        
        $controllers->get('/modules', function () {
            return JsonResponse::create($this->serviceManager->get('moduleService')->findAll($_GET));
        });

        $controllers->post('/modules/import', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'POST.MODULES.IMPORT')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('contentImporter')->importFiles('modules', $_FILES));
        });

        $controllers->get('/modules/{moduleId}', function ($moduleId) {
            if (!is_numeric($moduleId)) {
                $module = $this->serviceManager->get('moduleService')->findByCode($moduleId, $_GET);
                $moduleId = $module['id'];
            }

            return JsonResponse::create($this->serviceManager->get('moduleService')->find($moduleId, $_GET));
        });

        $controllers->put('/modules/{moduleId}', function ($moduleId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'PUT.MODULES')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('moduleService')->update($moduleId, $_POST));
        });

        $controllers->get('/modules/{moduleId}/questions', function ($moduleId) {
            return JsonResponse::create($this->serviceManager
                ->get('questionService')->findForModule($moduleId, null, $_GET));
        });

        $controllers->get('/modules/{moduleId}/types/{type}/questions', function ($moduleId, $type) {
            return JsonResponse::create($this->serviceManager
                ->get('questionService')->findForModule($moduleId, $type, $_GET));
        });

        // this should configure a question to this module type not add a question
        $controllers->post('/modules/{moduleId}/types/{type}/questions/{questionId}',
            function ($moduleId, $type, $questionId) {

            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'POST.QUESTIONS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('moduleService')
                ->addQuestion($moduleId, $type, $questionId));
        });
            
        return $controllers;
    }
}