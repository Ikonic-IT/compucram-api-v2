<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class Enrollment implements ControllerProviderInterface
{
    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * Enrollment constructor.
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
        
        $controllers->get('/enrollments', function () {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'GET.ENROLLMENTS')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('enrollmentService')->findAll($_GET));
        });

        $controllers->post('/enrollments', function () {
            return JsonResponse::create($this->serviceManager->get('enrollmentService')->save($_POST));
        });

        $controllers->post('/pending-enrollments', function () {
            return JsonResponse::create($this->serviceManager->get('enrollmentService')->savePending($_POST));
        });

        $controllers->get('/enrollments/{enrollmentId}', function ($enrollmentId) {
            return JsonResponse::create($this->serviceManager->get('enrollmentService')->find($enrollmentId, $_GET));
        });
        
        $controllers->put('/enrollments/{enrollmentId}', function ($enrollmentId) {
            return JsonResponse::create($this->serviceManager->get('enrollmentService')->update($enrollmentId, $_POST));
        });

        $controllers->post('/enrollments/{enrollmentId}/enable', function ($enrollmentId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'POST.ENROLLMENTS.ENABLE')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('enrollmentService')->enable($enrollmentId));
        });

        $controllers->post('/enrollments/{enrollmentId}/disable', function ($enrollmentId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'POST.ENROLLMENTS.DISABLE')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('enrollmentService')->disable($enrollmentId));
        });

        $controllers->post('/enrollments/{enrollmentId}/extend', function ($enrollmentId) {
            if (!$this->serviceManager->get('rbac')->isGranted($this->serviceManager->get('user')
                ->getRole(), 'POST.ENROLLMENTS.EXTEND')) {
                throw new \Exception("Not authorized", 403);
            }

            return JsonResponse::create($this->serviceManager->get('enrollmentService')->extend($enrollmentId, $_POST));
        });
        
        $controllers->get('/enrollments/{enrollmentId}/simulated-exam-attempts', function ($enrollmentId) {
            $params = $_GET;
            $params['type'] = 'simulatedexam';
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptService')->findForEnrollment($enrollmentId, $params));
        });

        $controllers->get('/enrollments/{enrollmentId}/pre-assessment-attempts', function ($enrollmentId) {
            $params = $_GET;
            $params['type'] = 'pre';
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptService')->findForEnrollment($enrollmentId, $params));
        });

        $controllers->get('/enrollments/{enrollmentId}/assessment-attempts', function ($enrollmentId) {
            $params = $_GET;
            return JsonResponse::create($this->serviceManager->get('assessmentAttemptService')->findForEnrollment($enrollmentId, $params));
        });
        
        $controllers->get('/enrollments/{enrollmentId}/modules/{moduleId}/{type}-attempt-questions', function ($enrollmentId, $moduleId, $type) {
            $params = $_GET;
            $params['type'] = $type;
            return JsonResponse::create($this->serviceManager->get('moduleAttemptQuestionService')->findLatestForEnrollmentModule($enrollmentId, $moduleId, $params));
        });
        
        $controllers->get('/enrollments/{enrollmentId}/modules/{moduleId}/{type}-attempts', function ($enrollmentId, $moduleId, $type) {
            $params = $_GET;
            $params['type'] = $type;
            return JsonResponse::create($this->serviceManager->get('moduleAttemptService')->findForEnrollmentModule($enrollmentId, $moduleId, $params));
        });
        
        $controllers->post('/enrollments/{enrollmentId}/modules/{moduleId}/attempts', function ($enrollmentId, $moduleId) {
            return JsonResponse::create($this->serviceManager->get('moduleAttemptService')->createAttempt($enrollmentId, $moduleId, $_POST));
        });

        $controllers->get('/enrollments/{enrollmentId}/progresses', function ($enrollmentId) {
            return JsonResponse::create($this->serviceManager->get('progressService')->findForEnrollment($enrollmentId, $_GET));
        });
        
        $controllers->put('/enrollments/{enrollmentId}/progresses', function ($enrollmentId) {
            return JsonResponse::create($this->serviceManager->get('progressService')->updateBulk($_POST));
        });
            
        $controllers->get('/enrollments/{enrollmentId}/progresses/{progressId}', function ($enrollmentId, $progressId) {
            return JsonResponse::create($this->serviceManager->get('progressService')->find($progressId, $_GET));
        });

        $controllers->put('/enrollments/{enrollmentId}/progresses/{progressId}', function ($enrollmentId, $progressId) {
            return JsonResponse::create($this->serviceManager->get('progressService')->update($progressId, $_POST));
        });

        $controllers->post('/enrollments/{enrollmentId}/progresses/{progressId}/recalculate', function ($enrollmentId, $progressId) {
            return JsonResponse::create($this->serviceManager->get('progressService')->recalculateBasedOnProgressQuestion($progressId));
        });

        $controllers->post('/enrollments/{enrollmentId}/modules/{moduleId}/{type}-progresses', function ($enrollmentId, $moduleId, $type) {
            $params = $_POST;
            $params['type'] = $type;
            return JsonResponse::create($this->serviceManager->get('enrollmentService')->createModuleProgress($enrollmentId, $moduleId, $params));
        });
        
        $controllers->get('/enrollments/{enrollmentId}/progresses/{progressId}/questions', function ($enrollmentId, $progressId) {
            return JsonResponse::create($this->serviceManager->get('progressQuestionService')->findForProgress($progressId, $_GET));
        });

        /**
         * @todo in the future, remove this and have the app make two separate calls
         */
        $controllers->get('/enrollments/{enrollmentId}/progresses/{progressId}/details', function ($enrollmentId, $progressId) {
            return JsonResponse::create($this->serviceManager->get('progressQuestionService')->findForProgressDetails($progressId, $_GET));
        });

        $controllers->put('/enrollments/{enrollmentId}/progresses/{progressId}/questions/{questionId}', function ($enrollmentId, $progressId, $questionId) {
            return JsonResponse::create($this->serviceManager->get('progressQuestionService')->update($questionId, $_POST));
        });
        
        return $controllers;
    }
}