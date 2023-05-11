<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class Question implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Question constructor.
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

        $controllers->post('/questions/search', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'POST.QUESTIONS.SEARCH')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('questionService')->search($_POST));
        });

//        $controllers->get('/questions', function () {
//            return JsonResponse::create($this->serviceManager->get('questionService')->findAll($_GET));
//        });

        $controllers->post('/questions', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'POST.QUESTIONS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('questionService')->save($_POST));
        });

        $controllers->get('/questions/{questionId}', function ($questionId) {
            return JsonResponse::create($this->serviceManager->get('questionService')->find($questionId, $_GET));
        });

        $controllers->put('/questions/{questionId}', function ($questionId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'PUT.QUESTIONS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('questionService')->update($questionId, $_POST));
        });


        $controllers->get('/questions/{questionId}/audits', function ($questionId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'GET.QUESTIONS.AUDITS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('questionService')->findAudits($questionId, $_GET));
        });

        $controllers->get('/questions/{questionId}/answers/audits', function ($questionId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'GET.QUESTIONS.ANSWERS.AUDITS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('answerService')->findAudits($questionId, $_GET));
        });
            
        return $controllers;
    }
}