<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class QuestionBank implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * QuestionBank constructor.
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

        $controllers->get('/question-banks/{questionBankId}/questions', function ($questionBankId) {
            return JsonResponse::create($this->serviceManager->get('questionService')->findForQuestionBank($questionBankId, $_GET));
        });

        $controllers->get('/question-banks/{questionBankId}/modules', function ($questionBankId) {
            return JsonResponse::create($this->serviceManager->get('moduleService')->findForQuestionBank($questionBankId, $_GET));
        });

        $controllers->post('/question-banks/{questionBankId}/questions', function ($questionBankId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'POST.QUESTIONS')) {
                throw new \Exception("Not authorized", 403);
            }

            $_POST['questionBankId'] = $questionBankId;

            return JsonResponse::create($this->serviceManager->get('questionService')->save($_POST));
        });

        return $controllers;
    }
}