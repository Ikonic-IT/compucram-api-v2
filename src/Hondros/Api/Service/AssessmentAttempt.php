<?php

namespace Hondros\Api\Service;

use Hondros\Api\Service\ServiceAbstract;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Api\Util\Helper\ArrayUtil;
use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;

class AssessmentAttempt extends ServiceAbstract
{
    use ArrayUtil {}
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\AssessmentAttempt';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'AssessmentAttempt';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\AssessmentAttempt
     */
    protected $repository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Enrollment
     */
    protected $enrollmentRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\ExamModule
     */
    protected $examModuleRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Question
     */
    protected $questionRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Progress
     */
    protected $progressRepository;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\AssessmentAttempt $repository, 
        Repository\Enrollment $enrollmentRepository, Repository\ExamModule $examModuleRepository, Repository\Question $questionRepository,
        Repository\Progress $progressRepository) 
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->examModuleRepository = $examModuleRepository;
        $this->questionRepository = $questionRepository;
        $this->progressRepository = $progressRepository;
    }
    
    public function findForEnrollment($enrollmentId, $params)
    {
        $paginator = $this->repository->findForEnrollment($enrollmentId, $params);
        
        return new DoctrineCollection($paginator, self::ENTITY_STRATEGY);
    }

    /**
     * @param int $enrollmentId
     * @param array $params
     * @return DoctrineSingle
     */
    public function createAttempt($enrollmentId, $params)
    {
        // for now until we find a better way, remove objects
        unset($params['enrollment']);

        // validate required params
        if (!array_key_exists('type', $params)) {
            throw new InvalidArgumentException("Missing required parameter type");
        }
        
        $type = $params['type'];
        
        // validate enrollment
        $enrollment = $this->enrollmentRepository->find($enrollmentId);
        
        if (empty($enrollment)) {
            throw new InvalidArgumentException("Unable to find enrollment {$enrollmentId}");
        }
        
        // get all exam modules for the bank ids
        $examModules = $this->examModuleRepository->findBy(['exam' => $enrollment->getExam()->getId()]);

        if (empty($examModules)) {
            throw new InvalidArgumentException("Unable to find exam modules for exam {$enrollment->getExam()->getId()}");
        }
        
        $totalQuestions = 0;
        $moduleQuestions = [];
        /** @var Entity\ExamModule $examModule */
        foreach ($examModules as $examModule) {
            if ($type == 'pre') {
                $moduleQuestions[$examModule->getModule()->getId()] = $this->questionRepository->getRandomQuestionIds(
                    $examModule->getModule()->getId(),
                    Entity\ModuleQuestion::TYPE_PREASSESSMENT,
                    $examModule->getPreassessmentQuestions(),
                    true);
            } else {
                $moduleQuestions[$examModule->getModule()->getId()] = $this->questionRepository->getRandomQuestionIds(
                    $examModule->getModule()->getId(),
                    Entity\ModuleQuestion::TYPE_EXAM,
                    $examModule->getExamQuestions());
            }
        }

        // randomize question order
        $questionIdToModuleId = [];
        foreach ($moduleQuestions as $moduleId => $value) {
            foreach ($value as $questionId) {
                $questionIdToModuleId[$questionId] = $moduleId;
            }
        }
        $questionIdToModuleId = $this->shuffleMaintainKeys($questionIdToModuleId);

        // create attempt
        $assessmentAttempt = new Entity\AssessmentAttempt();
        $this->entityManager->persist($assessmentAttempt);
        
        // create all question attempts
        $sort = 0;
        foreach ($questionIdToModuleId as $questionId => $moduleId) {
            $assessmentAttemptQuestion = new Entity\AssessmentAttemptQuestion();
            $assessmentAttemptQuestion->setAssessmentAttempt($assessmentAttempt)
                ->setModule($this->entityManager->getReference('Hondros\Api\Model\Entity\Module', $moduleId))
                ->setModuleId($moduleId)
                ->setQuestion($this->entityManager->getReference('Hondros\Api\Model\Entity\Question', $questionId))
                ->setQuestionId($questionId)
                ->setSort(++$sort)
                ->setCreated(new \DateTime());

            $this->entityManager->persist($assessmentAttemptQuestion);

            // track questions
            $totalQuestions++;
        }
        
        // update attempt
        $assessmentAttempt->setEnrollment($enrollment)
            ->setCreated(new \DateTime())
            ->setQuestionCount($totalQuestions)
            ->setType($type);
        
        // save
        $this->entityManager->flush();
        
        // return the module attempt info, with questions and answers
        return new DoctrineSingle($assessmentAttempt, self::ENTITY_STRATEGY);
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

        return $this->createAttempt($params['enrollmentId'], $params);
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

        $now = new \DateTime();
        /** @var Entity\AssessmentAttempt $assessment */
        $assessment = $this->repository->find($params['id']);
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\AssessmentAttempt();
        $hydrator = $strategy->getHydrator();
        $assessment = $hydrator->hydrate($params, $assessment);
        $assessment->setModified($now);

        // COMP-740 make sure completed is realistic
        if (!empty($params['completed']) && $assessment->getCompleted() < $assessment->getCreated()) {
            $assessment->setCompleted($now);
        }
    
        $this->entityManager->persist($assessment);
        $this->entityManager->flush();
    
        return new DoctrineSingle($assessment, self::ENTITY_STRATEGY);
    }
}
