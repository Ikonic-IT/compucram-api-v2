<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class Exam implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Exam constructor.
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

        $controllers->get('/exams', function () {
            return JsonResponse::create($this->serviceManager->get('examService')->findAll($_GET));
        });

        $controllers->post('/exams/import', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'POST.EXAMS.IMPORT')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('contentImporter')->importFiles('exams', $_FILES));
        });

        $controllers->get('/exams/{examId}', function ($examId) {
            if (!is_numeric($examId)) {
                $exam = $this->serviceManager->get('examService')->findByCode($examId, $_GET);
                $examId = $exam['id'];
            }
            
            return JsonResponse::create($this->serviceManager->get('examService')->find($examId, $_GET));
        });

        $controllers->put('/exams/{examId}', function ($examId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'PUT.EXAMS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('examService')->update($examId, $_POST));
        });
        
        $controllers->get('/exams/{examId}/modules', function ($examId) {
            return JsonResponse::create($this->serviceManager->get('examModuleService')->findForExam($examId, $_GET));
        });

        $controllers->put('/exams/{examId}/modules', function ($examId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'PUT.EXAMS.MODULES')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('examModuleService')->updateBulk($examId, $_POST));
        });

        $controllers->post('/exams/{examId}/modules/{moduleId}', function ($examId, $moduleId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'POST.EXAMS.MODULES')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('examModuleService')->save($examId, $moduleId, $_POST));
        });

        $controllers->delete('/exams/{examId}/modules/{moduleId}', function ($examId, $moduleId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'DELETE.EXAMS.MODULES')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('examModuleService')->delete($examId, $moduleId, $_POST));
        });

        $controllers->put('/exams/{examId}/modules/{moduleId}', function ($examId, $moduleId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'PUT.EXAMS.MODULES')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('examModuleService')->update($examId, $moduleId, $_POST));
        });

        $controllers->post('/exams/{examId}/export', function ($examId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')->getRole(), 'POST.EXAMS.EXPORT')) {
                throw new \Exception("Not authorized", 403);
            }

            // @todo allow more time as a quick fix for now. Should make async process
            set_time_limit(180);

            return JsonResponse::create($this->serviceManager->get('exportContentService')->exportExam($examId, $_POST));
        });

        return $controllers;
    }
}