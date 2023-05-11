<?php

namespace Hondros\Api\Service;

use Hondros\Api\Service\ServiceAbstract;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;

class Progress extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\Progress';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'Progress';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\Progress
     */
    protected $repository;

    /**
     * @var \Hondros\Api\Model\Repository\ProgressQuestion
     */
    protected $progressQuestionRepository;

    /**
     * @var \Hondros\Api\Model\Repository\ModuleAttempt
     */
    protected $moduleAttemptRepository;

    /**
     * @var \Hondros\Api\Model\Repository\AssessmentAttempt
     */
    protected $assessmentAttemptRepository;

    /**
     * @var \Hondros\Api\Model\Repository\AssessmentAttemptQuestion
     */
    protected $assessmentAttemptQuestionRepository;

    /**
     * @var \Hondros\Api\Model\Repository\Enrollment
     */
    protected $enrollmentRepository;

    /**
    * @var \Hondros\Api\Model\Repository\ExamModule
    */
    protected $examModuleRepository;

    /**
     * Progress constructor.
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param Repository\Progress $repository
     * @param Repository\ProgressQuestion $progressQuestionRepository
     * @param Repository\ModuleAttempt $moduleAttemptRepository
     * @param Repository\AssessmentAttempt $assessmentAttemptRepository
     * @param Repository\AssessmentAttemptQuestion $assessmentAttemptQuestionRepository
     * @param Repository\Enrollment $enrollmentRepository
     * @param Repository\ExamModule $examModuleRepository
     */
    public function __construct(
        EntityManager $entityManager,
        Logger $logger,
        Repository\Progress $repository,
        Repository\ProgressQuestion $progressQuestionRepository,
        Repository\ModuleAttempt $moduleAttemptRepository,
        Repository\AssessmentAttempt $assessmentAttemptRepository,
        Repository\AssessmentAttemptQuestion $assessmentAttemptQuestionRepository,
        Repository\Enrollment $enrollmentRepository,
        Repository\ExamModule $examModuleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->progressQuestionRepository = $progressQuestionRepository;
        $this->moduleAttemptRepository = $moduleAttemptRepository;
        $this->assessmentAttemptRepository = $assessmentAttemptRepository;
        $this->assessmentAttemptQuestionRepository = $assessmentAttemptQuestionRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->examModuleRepository = $examModuleRepository;
    }

    /**
     * @param array $params
     * @return DoctrineSingle
     */
    public function save($params)
    {
        // create new
        if (!empty($params['id'])) {
            return $this->update($params['id'], $params);
        }
    }

    /**
     * @param int $id
     * @param array $params
     * @return DoctrineSingle
     */
    public function update($id, $params)
    {
        if (empty($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid id.", 400);
        }

        // for now until we find a better way, remove objects
        unset($params['enrollment']);
        unset($params['module']);
    
        // get module and see what it's current stats are
        $progress = $this->repository->find($params['id']);
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Progress();
        $hydrator = $strategy->getHydrator();
        $progress = $hydrator->hydrate($params, $progress);
        $progress->setModified(new \DateTime());
    
        $this->entityManager->persist($progress);
        $this->entityManager->flush();
    
        return new DoctrineSingle($progress, self::ENTITY_STRATEGY);
    }
    
    /**
     * Takes in a collection of progresses and updates them all
     *
     * @param array $data
     * @return \Hondros\Common\DoctrineCollection
     */
    public function updateBulk($data)
    {
        $collection = [];
    
        if (empty($data) || !is_array($data[0])) {
            throw new InvalidArgumentException("Data must be an array for bulk updates.");
        }
    
        // now loop, clean up, and get ids
        foreach ($data as &$row) {
            // for now until we find a better way, remove objects - need to fix the hydrator logic
            unset($row['enrollment']);
            unset($row['module']);
    
            $collection[$row['id']] = $row;
        }
    
        // don't need data anymore
        unset($data);
    
        // needed for validation to make sure they really are there but slower as we need to select
        /** @var Entity\Progress[] $progresses */
        $progresses = $this->repository->findById(array_keys($collection));
    
        // setup
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Progress();
        $hydrator = $strategy->getHydrator();
    
        foreach ($progresses as $progress) {
            $progress = $hydrator->hydrate($collection[$progress->getId()], $progress);
            $progress->setModified(new \DateTime());
            $this->entityManager->persist($progress);
        }
    
        // update all
        $this->entityManager->flush();
    
        return new DoctrineCollection($progresses, self::ENTITY_STRATEGY);
    }

    /**
     * Looks at the progress questions and recalculates the progress info
     *
     * Takes active/inactive question status into consideration
     *
     * @param int $id progress id
     * @return array
     */
    public function recalculateBasedOnProgressQuestion($id)
    {
        // counters
        $questionCount = 0;
        $correct = 0;
        $incorrect = 0;
        $bookmarked = 0;
        $score = 0;
        $attempts = 0;

        /** @var Entity\Progress $progress */
        $progress = $this->repository->findOneById($id);

        if (empty($progress)) {
            throw new \InvalidArgumentException("No progress found for {$id}");
        }

        // the pre-assessment score is the starting score if taken
        // bug in the system cause some students to have multiple empty pre-assessments
        // so get one with a score
        $assessment = $this->assessmentAttemptRepository->findOneBy([
            'enrollment' => $progress->getEnrollmentId(),
            'type' => 'pre'
        ], ['score' => 'DESC']);

        // if there is one, get the rest and do the math
        if (!empty($assessment) && !empty($assessment->getCompleted())) {
            // now get all the questions for this module
            /** @var Entity\AssessmentAttemptQuestion[] $assessmentQuestions */
            $assessmentQuestions = $this->assessmentAttemptQuestionRepository->findBy([
                'assessmentAttempt' => $assessment->getId(),
                'module' => $progress->getModuleId()
            ]);

            $totalAssessmentQuestions = 0;
            $totalAssessmentCorrect = 0;

            foreach ($assessmentQuestions as $assessmentQuestion) {
                $totalAssessmentQuestions++;
                $totalAssessmentCorrect += $assessmentQuestion->getCorrect() ? 1 : 0;
            }

            $score = $totalAssessmentCorrect > 0
                ? ceil($totalAssessmentCorrect / $totalAssessmentQuestions * 100)
                : 0;
        }

        // pull back progress questions only if the question it links to is active
        $progressQuestions = $this->progressQuestionRepository->findByProgressId($id, ['active' => true]);

        foreach ($progressQuestions as $progressQuestion) {
            // track questions
            $questionCount++;

            // bookmarked?
            if ($progressQuestion->getBookmarked()) {
                $bookmarked++;
            }

            // if not answered it can't be correct/incorrect
            if ($progressQuestion->getAnswered() && $progressQuestion->getCorrect()) {
                $correct++;
            }

            if ($progressQuestion->getAnswered() && !$progressQuestion->getCorrect()) {
                $incorrect++;
            }
        }

        // study and practice scores work differently
        if ($progress->getType() == $progress::TYPE_STUDY) {
            $score = ($correct / $questionCount) * 100;
        } else if ($progress->getType() == $progress::TYPE_PRACTICE) {
            // get all module attempts and sum the score*multiplier
            /** @var Entity\ModuleAttempt[] $moduleAttempts */
            $moduleAttempts = $this->moduleAttemptRepository->findForEnrollmentModule($progress->getEnrollmentId(), $progress->getModuleId(), [
                'type' => Entity\Progress::TYPE_PRACTICE
            ]);

            // get the score from the other module attempts
            foreach ($moduleAttempts as $moduleAttempt) {
                $attempts++;
                $score += $moduleAttempt->getScore() * Entity\Progress::PRACTICE_SCORE_MULTIPLIER;
            }

            $progress->setAttempts($attempts);
        } else {
            // shouldn't be here
        }

        // update progress
        $progress->setQuestionCount($questionCount);
        $progress->setCorrect($correct);
        $progress->setIncorrect($incorrect);
        $progress->setBookmarked($bookmarked);
        $progress->setScore($score > 100 ? 100 : ceil($score));
        $progress->setModified(new \DateTime());

        // save if anything changed
        $this->entityManager->flush();

        return new DoctrineSingle($progress, self::ENTITY_STRATEGY);
    }

    /**
     * @param int $studentId
     * @param int $enrollmentId
     * @param array $params
     * @return DoctrineSingle
     */
    public function getReadinessScore($studentId, $enrollmentId, $params) {

        $readinessScore = new \Hondros\Api\Model\Entity\ReadinessScore();

        $enrollment = $this->getEnrollment($studentId, $enrollmentId);
        $data = $this->getReadinessScoreData($enrollment);

        $score = $this->calculateReadinessScore($data);

        $readinessScore->setStudentId($enrollment->getUserId());
        $readinessScore->setEnrollmentId($enrollment->getId());
        $readinessScore->setExternalOrderId($enrollment->getExternalOrderId());
        $readinessScore->setReadinessScore($score);

        return new DoctrineSingle($readinessScore, 'ReadinessScore');
    }

    /**
     * @param int $studentId
     * @param int $enrollmentId
     * @param array $params
     * @return DoctrineSingle
     */
    public function getEnrollmentScoreCardMetrics($studentId, $enrollmentId, $params)
    {
        $enrollment = $this->getEnrollment($studentId, $enrollmentId);
        $data = $this->getScoreCardData($enrollment);
        $scorecard = $this->calculateScoreCard($data, $enrollment);
        return new DoctrineSingle($scorecard, 'Scorecard', ['examCategoryScores','simulatedExamScores']);
    }

    /**
     * @param int $studentId
     * @param array $params
     * @return DoctrineCollection
     */    
    public function getStudentScoreCardMetrics($studentId, $params)
    {
        $scoreCards = array();

        $enrollments = $this->enrollmentRepository->findBy([
            'user' => $studentId
        ]);

        if (empty($enrollments)) {
            throw new InvalidArgumentException("Unable to find enrollments for student {$studentId}");
        }

        foreach ($enrollments as $enrollment) {
            $data = $this->getScoreCardData($enrollment);
            $scoreCard = $this->calculateScoreCard($data, $enrollment);
            $scoreCards[] = $scoreCard;
        }

        return new DoctrineCollection($scoreCards, 'Scorecard', ['examCategoryScores', 'simulatedExamScores']);
    }

    private function getEnrollment($studentId, $enrollmentId)
    {
        if (empty($studentId) || empty($enrollmentId)) {
            throw new InvalidArgumentException("Empty student id or enrollment id");
        }

        $enrollment = $this->enrollmentRepository->findOverride($enrollmentId, ['progresses']);

        if (empty($enrollment)) {
            throw new InvalidArgumentException("Unable to find enrollment {$enrollmentId}");
        }

        if ($enrollment->getUserId() != $studentId) {
            throw new InvalidArgumentException("Enrollment {$enrollmentId} does not belong to student {$studentId}");
        }

        return $enrollment;
    }

    private function getReadinessScoreData($enrollment)
    {
        if (empty($enrollment))
            return array();

        $enrollmentId = $enrollment->getId();

        $progresses = empty($enrollment->getProgresses()) 
            ? $this->repository->findBy(['enrollment' => $enrollmentId])
            : $enrollment->getProgresses();

        $assessmentAttempts = $this->assessmentAttemptRepository->findBy([
            'enrollment' => $enrollmentId
        ]);

        $examModules = $this->examModuleRepository->findBy([
            'exam' => $enrollment->getExamId()
        ]);

        $data['enrollment'] =  $enrollment;
        $data['progresses'] = $progresses;
        $data['assessmentAttempts'] = $assessmentAttempts;
        $data['examModules'] = $examModules;

        return $data;
    }

    private function calculateReadinessScore($data)
    {
        // calculate score for all study and practice test for all exam categories / modules
        // even though there could be multiple practice test sessions for an exam category / module
        // the progress table only stores the latest study / practice test score
        // therefore there is only one study and one practice score per module

        $examCategoryScore = 0;

        if (!empty($data['examModules']) 
            && !empty($data['progresses'])
        )
        {
            $examCategoryTotalScore = 0;

            foreach ($data['examModules'] as $examModule) {
                
                // calculate per exam category / module score
                $studyVocabScore = 0;
                $practiceTestScore = 0;
                
                foreach ($data['progresses'] as $progress) {

                    if ($progress->getModuleId() == $examModule->getModuleId()) {
                        if ($progress->getType() == Entity\Progress::TYPE_STUDY) {
                            $studyVocabScore = $progress->getScore();
                        } elseif ($progress->getType() == Entity\Progress::TYPE_PRACTICE) {
                            $practiceTestScore = $progress->getScore();
                        }
                    }
                }

                // calculate exam category score
                $examCategoryTotalScore += ceil(($studyVocabScore * .1) + ($practiceTestScore * .9));
            }

            // Dividing the total score by all exam modules not just by exam modules with scores
            $averageExamCategoryScore = $examCategoryTotalScore > 0 ? ($examCategoryTotalScore / count($data['examModules'])) : 0;
            $examCategoryScore = $averageExamCategoryScore > 100 ? 100 : $averageExamCategoryScore;
        }

        // calculate score for all completed simulated exams taken
        $simulatedExamsScore = 0;

        if (!empty($data['assessmentAttempts']))
        {
            foreach ($data['assessmentAttempts'] as $assessmentAttempt) {
                // take into account only completed simulated exams
                if ($assessmentAttempt->getType() == Entity\AssessmentAttempt::TYPE_EXAM 
                    // completed
                    && !empty($assessmentAttempt->getCompleted())
                    // only count score if greater than 75
                    && $assessmentAttempt->getScore() >= 75
                    ) {
                    
                    // each simulated exam only counts for 1/4 of the exam score
                    $simulatedExamsScore += $assessmentAttempt->getScore() * .25;            
                }
            }
        }

        // weighted practice score + weighted simulated exam score
        $readinessScore = $examCategoryScore * 0.6 + $simulatedExamsScore * 0.4;

        if ($readinessScore >= 100) {
            $readinessScore = 100;
        } else if ($readinessScore > 0) {
            // if progress, round to nearest 5
            $readinessScore = 5 * round($readinessScore / 5);
        }

        return $readinessScore;
    }

    private function getScoreCardData($enrollment)
    {
        if (empty($enrollment))
            return array();

        // start with readiness data
        // populates enrollment, progresses, assessmentAttempts, examModules
        $data = $this->getReadinessScoreData($enrollment);

        // add missing moduleAttempts
        $moduleAttempts = $this->moduleAttemptRepository->findBy([
            'enrollment' => $enrollment->getId()
        ]);

        $data['moduleAttempts'] =  $moduleAttempts;

        return $data;
    }

    private function calculateScoreCard($data, $enrollment)
    {
        $scoreCard = new \Hondros\Api\Model\Entity\Scorecard();
        $scoreCard->setEnrollmentId($enrollment->getId());
        $scoreCard->setExternalOrderId($enrollment->getExternalOrderId());

        $secondsStudied = 0;

        // pick the latest completed pre-assessment or simulated exam
        // calculate examScoreAverage from scores of completed simulated exams
        // add totaltime from any pre-assessment and simulated exams regardless if complete or not
        
        if (!empty($data['assessmentAttempts'])) {

            $sumOfCompletedSimulatedExamScores = 0;
            $numOfCompletedSimulatedExams = 0;

            $lastCompletedAssessmentAttempt = null;
            foreach ($data['assessmentAttempts'] as $assessmentAttempt) {
                // add totaltime in pre-assessment and simulated-exam, regardless if complete or not
                $secondsStudied += $assessmentAttempt->getTotalTime();

                // completed pre-assessment or simulated exam
                if (!empty($assessmentAttempt->getCompleted())) {
                    if (empty($lastCompletedAssessmentAttempt)) {
                        $lastCompletedAssessmentAttempt = $assessmentAttempt;
                    }
                    else if ($lastCompletedAssessmentAttempt->getCompleted() < $assessmentAttempt->getCompleted()) {
                        $lastCompletedAssessmentAttempt = $assessmentAttempt;
                    }
                    // simulated exam
                    if ($assessmentAttempt->getType() == Entity\AssessmentAttempt::TYPE_EXAM) {
                        $scoreCard->addSimulatedExamScore($assessmentAttempt);
                        $sumOfCompletedSimulatedExamScores += $assessmentAttempt->getScore(); 
                        $numOfCompletedSimulatedExams++;        
                    }  
                }
            }

            if ($numOfCompletedSimulatedExams > 0) {
                $scoreCard->setExamScoreAverage($sumOfCompletedSimulatedExamScores > 0 ? ceil($sumOfCompletedSimulatedExamScores / $numOfCompletedSimulatedExams) : 0); 
            }
        }

        // add totaltime from study and practice tests, regardless if complete or not
        if (!empty($data['moduleAttempts'])) {
            foreach ($data['moduleAttempts'] as $moduleAttempt) {
                // add time spent in study and practice tests, regardless if complete or not
                $secondsStudied += $moduleAttempt->getTotalTime();
            }
        }

        // pick module practice test score if finished later than the last completed assessment attempt (pre-assessment or simulated exam)
        $moduleIdProgressScores = array();
        if (!empty($data['progresses'])) {
            foreach ($data['progresses'] as $progress) {
                // only of practice test and has score
                // the latest score stored in progress is for completed test
                if ($progress->getType() == Entity\Progress::TYPE_PRACTICE
                    // only has a score for completed tests
                    && $progress->getScore() > 0) {

                    // more recent then last completed assessment attempt
                    if (empty($lastCompletedAssessmentAttempt) || $lastCompletedAssessmentAttempt->getCompleted() < $progress->getModified()) {
                        $moduleIdProgressScores[$progress->getModuleId()] = $progress->getScore();
                    }
                }
            }
        }

        // fill any blanks from the last completed assessment attempt 
        $moduleNameScores = array();
        $moduleIdAssessmentScores = array();
        foreach ($data['examModules'] as $examModule) {
            $moduleId = $examModule->getModuleId();
            // use score if you already stored one from progresses
            if (!empty($moduleIdProgressScores[$moduleId])) {
                $moduleNameScores[$examModule->getName()] = $moduleIdProgressScores[$moduleId];
            }
            // if there is not a score stored pull from the lastCompletedAssessmentAttempt, if available
            else if (!empty($lastCompletedAssessmentAttempt)) {
                if (empty($moduleIdAssessmentScores)) {
                    $moduleIdAssessmentScores = $this->CalcModuleIdAssessmentScores($lastCompletedAssessmentAttempt);
                }
                $moduleNameScores[$examModule->getName()] = $moduleIdAssessmentScores[$moduleId];
            }
        }

        foreach ($moduleNameScores as $category => $score) {
            $scoreCard->addExamCategoryScore($category, $score);
        }

        $scoreCard->setReadinessScore($this->calculateReadinessScore($data));
        $scoreCard->setHoursStudied($secondsStudied > 0 ? round($secondsStudied / 3600, 2) : 0);

        // question bank
            // distinct correct question id count for module attempt questions/ active question count for study + practice questions
        if (!empty($data['enrollment'])) {
            $enrollment = $data['enrollment'];
            $questionBankData = $this->repository->getQuestionBankPercentCorrectData($enrollment->getId(), $enrollment->getExamId());

            if (!empty($questionBankData['inQuestionBank']) && !empty($questionBankData['correctlyAnswered'])) {
                $scoreCard->setQuestionBankPercentCorrect(round($questionBankData['correctlyAnswered'] / $questionBankData['inQuestionBank'] * 100));
            }
            
        }

        return $scoreCard;
    }

    private function CalcModuleIdAssessmentScores($assessmentAttempt) {

        $moduleIdAssessmentScores = array();
        foreach ($assessmentAttempt->getAssessmentAttemptQuestions() as $attemptQuestion) {
            $moduleId = $attemptQuestion->getModuleId();
            if (empty($moduleIdAssessmentScores[$moduleId])) {
                $moduleIdAssessmentScores[$moduleId] = array('correctAnswers'=>0, 'numOfQuestions'=>0);
            }
            $moduleIdAssessmentScores[$moduleId]['numOfQuestions']++;
            if ($attemptQuestion->getCorrect()) {
                $moduleIdAssessmentScores[$moduleId]['correctAnswers']++;
            }
        }

        foreach ($moduleIdAssessmentScores as $key => $val) {
            $moduleIdAssessmentScores[$key] = $val['correctAnswers'] == 0 || $val['numOfQuestions'] == 0 ? 0 : ceil($val['correctAnswers'] / $val['numOfQuestions'] * 100);
        }

        return $moduleIdAssessmentScores;
    }

}

/*     // DO NOT DELETE
    // following functions are not in use, they maybe used though in the future.
    public function getEnrollmentAssessmentAttempts($studentId, $enrollmentId) {
        $response = array();

        $enrollment = $this->getEnrollment($studentId, $enrollmentId);
        $exam = $enrollment->getExam();
        $data = $this->getScoreCardData($enrollment);

        foreach($data['simulatedExams'] as $simulatedExam) {
            // only include completed
            if (!empty($simulatedExam->getCompleted())) {
                $response['attempts'][$simulatedExam->getId()] = array( 
                    'AttemptId' => $simulatedExam->getId(),
                    'HoursStudied' => round($simulatedExam->getTotalTime()/3600, 2),
                    'ExamName' => $exam->getName(),
                    'DateStarted' => $simulatedExam->getCreated()->format('m/d/Y h:i A'),
                    'DateFinished' => $simulatedExam->getCompleted()->format('m/d/Y h:i A'),
                    'Score' => $simulatedExam->getScore()
                );
            }
        }

        $response['summary'] = $this->calculateScoreCard($data);

        // removing the readinesscore from the scorecard
        unset($response['summary']['ReadinessScore']);

        return $response;
    }

    public function findStudentattemptByAttemptId($metricData, $enrollmentId, $studentId, $params) {

        $response =[];

        $simulatedQuestion = [];

        foreach($metricData['modules'] as $module) {
            $modules[$module['moduleId']] = $module['name'];
            $moduleAttempt[$module['moduleId']] = array('correct'=>0,'viewed'=>0,'total'=>0);
        }
        
        foreach($metricData['questionattempts'] as $questionAttempt) {
            if(!isset($simulatedQuestion[$questionAttempt['moduleId']])){
                $simulatedQuestion[$questionAttempt['moduleId']] = array(
                    'correct'=>0,
                    'viewed'=>0,
                    'available'=>0,
                    'average' =>0,
                    'name'=>$modules[$questionAttempt['moduleId']] 
                );
            }
            $simulatedQuestion[$questionAttempt['moduleId']]['available'] += 1;
            if($questionAttempt['viewed']==true){
                $simulatedQuestion[$questionAttempt['moduleId']]['viewed'] += 1;
                if($questionAttempt['correct']==true){
                    $simulatedQuestion[$questionAttempt['moduleId']]['correct'] += 1;
                }
            } 
        }

        foreach($simulatedQuestion as $moduleId => $singleModuleAttempt) {
            if($singleModuleAttempt['correct'] == 0 || $singleModuleAttempt['viewed'] == 0) {
                $simulatedQuestion[$moduleId]['average']=0;
            } else {
                $simulatedQuestion[$moduleId]['average']=ceil(($singleModuleAttempt['correct']/$singleModuleAttempt['viewed'])*100);
            }
            
        }

        $response['ChapterBreakDown'] = $simulatedQuestion;
        
        // Max cap for strong and week modules
        $pivotal = 75;

        $modules = array();
        $moduleAttempt = array();
        $moduleAttemptBymodule = array();
        $moduleAttemptTotal =  array('correct'=>0,'viewed'=>0,'total'=>0, 'average'=>0);

        foreach($metricData['modules'] as $module) {
            $modules[$module['moduleId']] = $module['name'];
            $moduleAttempt[$module['moduleId']] = array('correct'=>0,'viewed'=>0,'total'=>0);
        }
        $response['HoursStudied'] = 0;
        $response['ExamScoreAverage'] = 0;
        foreach($metricData['simulatedExams'] as $exams) {
            $response['HoursStudied'] += $exams['totalTime'];
            $response['ExamScoreAverage'] += ceil($exams['score']/count($metricData['simulatedExams']));

            $singleAttempt = $metricData['attempts'][$exams['id']];
            foreach($singleAttempt as $attempts) {
                if($attempts['viewed']==true){
                    $moduleAttempt[$attempts['moduleId']]['viewed'] += 1;
                    $moduleAttemptTotal['viewed'] += 1;
                    if($attempts['correct']==true){
                        $moduleAttempt[$attempts['moduleId']]['correct'] += 1;
                        $moduleAttemptTotal['correct'] += 1;
                    }
                } 
                $moduleAttempt[$attempts['moduleId']]['total'] += 1;
                $moduleAttemptTotal['total'] += 1;
            }
            
        }

        $moduleAttemptTotal['average'] = ceil(($moduleAttemptTotal['viewed']/$moduleAttemptTotal['total'])*100);
        foreach($moduleAttempt as $moduleId => $singleModuleAttempt) {
            $moduleAttemptBymodule[$moduleId]=ceil(($singleModuleAttempt['correct']/$singleModuleAttempt['viewed'])*100);
        }

        $response['questionBankPercentCorrect'] = $moduleAttemptTotal['average'];
        
        $i = 0;
        asort($moduleAttemptBymodule); // sorting in descending order by association
        $response['WeakestChapters'] = [];
        foreach($moduleAttemptBymodule as $key => $val) {
            if($val <= $pivotal && $i < 3){
                $response['WeakestChapters'][] = $modules[$key];
                $i++;
            }
        }
        arsort($moduleAttemptBymodule); // sorting in ascending order by association
        $i = 0;
        $response['StrongestChapters'] = [];
        foreach($moduleAttemptBymodule as $key => $val) {
            if($val >= $pivotal && $i < 3){
                $response['StrongestChapters'][] = $modules[$key];
                $i++;
            }
        }
        
        $response['HoursStudied'] = round($response['HoursStudied']/3600, 2);
        
        return $response;
    }
 */