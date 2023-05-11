<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class ModuleAttempt implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * ModuleAttempt constructor.
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

        $controllers->post('/module-attempts', function () {
            return JsonResponse::create($this->serviceManager->get('moduleAttemptService')->save($_POST));
        });
            
        $controllers->get('/module-attempts/{moduleAttemptId}', function ($moduleAttemptId) {
            return JsonResponse::create($this->serviceManager->get('moduleAttemptService')->find($moduleAttemptId, $_GET));
        });

        $controllers->put('/module-attempts/{moduleAttemptId}', function ($moduleAttemptId) {
            return JsonResponse::create($this->serviceManager->get('moduleAttemptService')->update($moduleAttemptId, $_POST));
        });
        
        $controllers->get('/module-attempts/{moduleAttemptId}/question-attempts', function ($moduleAttemptId) {
            return JsonResponse::create($this->serviceManager->get('moduleAttemptQuestionService')->findForModuleAttempt($moduleAttemptId, $_GET));
        });
        
        $controllers->put('/module-attempts/{moduleAttemptId}/question-attempts', function ($moduleAttemptId) {
            return JsonResponse::create($this->serviceManager->get('moduleAttemptQuestionService')->updateBulk($_POST));
        });
            
        $controllers->get('/module-attempts/{moduleAttemptId}/progress-questions', function ($moduleAttemptId) {
            return JsonResponse::create($this->serviceManager->get('progressQuestionService')->findForModuleAttempt($moduleAttemptId, $_GET));
        });
            
        return $controllers;
    }
}