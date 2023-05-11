<?php

namespace Hondros\Api\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Hondros\ThirdParty\Symfony\Component\HttpFoundation\JsonResponse;
use Laminas\ServiceManager\ServiceManager;

class ReadinessScore implements ControllerProviderInterface
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
        
        $controllers->get('/student/{studentId}/enrollments/{enrollmentId}/readinessscore', function ($studentId, $enrollmentId) {
            return JsonResponse::create($this->serviceManager->get('progressService')->getReadinessScore($studentId, $enrollmentId, $_GET));
        });
        
        $controllers->get('/student/{studentId}/enrollments/{enrollmentId}/scorecardmetrics', function ($studentId, $enrollmentId) {
            return JsonResponse::create($this->serviceManager->get('progressService')->getEnrollmentScoreCardMetrics($studentId, $enrollmentId, $_GET));  
        });
        
        $controllers->get('/student/{studentId}/scorecardmetrics', function ($studentId) {
            return JsonResponse::create($this->serviceManager->get('progressService')->getStudentScoreCardMetrics($studentId, $_GET));  
        });

/*         // DO NOT DELETE
        // following endpoints are not used yet, they maybe used in the future. 
        $controllers->get('/student/{studentId}/enrollments/{enrollmentId}/assessmentattempts', function ($studentId, $enrollmentId) {
            return JsonResponse::create($this->serviceManager->get('progressService')->getEnrollmentAssessmentAttempts($studentId, $enrollmentId, $_GET));
        });
        
        $controllers->get('/student/{studentId}/enrollments/{enrollmentId}/assessment/{attemptId}/attemptscore', function ($studentId, $enrollmentId, $attemptId) {
         
            $params = $_GET;
            $params['type'] = 'simulatedexam';
            $params['completed'] = '';

            $metricdata['simulatedExams'] = $this->serviceManager->get('assessmentAttemptService')->findForCompletedEnrollment($enrollmentId, $params);
            
            foreach($metricdata['simulatedExams'] as $simulatedExam) {
                if( $simulatedExam['id'] == $attemptId ) {
                    $metricdata['simulatedExam'] =  $simulatedExam;
                }
            }
            $attemptedquestions = $this->serviceManager->get('assessmentAttemptQuestionService')->findForAssessmentAttempt($attemptId, $_GET); 
            $metricdata['questionattempts'] = json_decode(json_encode($attemptedquestions),true);
            
            $enrollment = $this->serviceManager->get('enrollmentService')->find($enrollmentId, $_GET);
            $metricdata['modules'] = $this->serviceManager->get('examModuleService')->findForExam($enrollment['examId'], $_GET);
            
             foreach($metricdata['simulatedExams'] as $attment) {
                 $attemptedquestions = $this->serviceManager->get('assessmentAttemptQuestionService')->findForAssessmentAttempt($attment['id'], $_GET); 
                 $metricdata['attempts'][$attment['id']] = json_decode(json_encode($attemptedquestions),true);
             }

            $response = $this->serviceManager->get('progressService')->findStudentattemptByAttemptId($metricdata, $enrollmentId, $studentId, $_GET);
            
            return JsonResponse::create($response);
        }); */

        return $controllers;
    }
}