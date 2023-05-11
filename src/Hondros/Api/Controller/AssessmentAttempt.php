<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class AssessmentAttempt implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * AssessmentAttempt constructor.
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

        $controllers->post('/assessment-attempts', function () {
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptService')->save($_POST));
        });

        $controllers->put('/assessment-attempts/{assessmentAttemptId}', function ($assessmentAttemptId) {
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptService')->update($assessmentAttemptId, $_POST));
        });

        // @todo compucram tool is currently calling this passing in question/answer join bypassing cache. Probably better to make two calls
        $controllers->get('/assessment-attempts/{assessmentAttemptId}/question-attempts', function ($assessmentAttemptId) {
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptQuestionService')->findForAssessmentAttempt($assessmentAttemptId, $_GET));
        });

        $controllers->put('/assessment-attempts/{assessmentAttemptId}/question-attempts', function ($assessmentAttemptId) {
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptQuestionService')->updateBulk($_POST));
        });

        $controllers->put('/assessment-attempts/{assessmentAttemptId}/question-attempts/{assessmentAttemptQuestionId}',
            function ($assessmentAttemptId, $assessmentAttemptQuestionId) {
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptQuestionService')->update($assessmentAttemptQuestionId, $_POST));
        });
            
        return $controllers;
    }
}