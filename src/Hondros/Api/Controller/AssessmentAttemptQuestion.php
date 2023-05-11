<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class AssessmentAttemptQuestion implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * AssessmentAttemptQuestion constructor.
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

        $controllers->get('/assessment-attempt-questions/{assessmentAttemptQuestionId}', function ($assessmentAttemptQuestionId) {
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptQuestionService')->find($assessmentAttemptQuestionId, $_GET));
        });
        
        $controllers->post('/assessment-attempt-questions/{assessmentAttemptQuestionId}', function ($assessmentAttemptQuestionId) {
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptQuestionService')->save($_POST));
        });
            
        return $controllers;
    }
}