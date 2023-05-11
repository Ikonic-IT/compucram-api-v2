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
use Exception;

class ModuleAttempt extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\ModuleAttempt';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'ModuleAttempt';

    /**
     * % where we don't update the progress score
     */
    const SCORE_CUT_OFF = 60;
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\ModuleAttempt
     */
    protected $repository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Enrollment
     */
    protected $enrollmentRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Module
     */
    protected $moduleRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Question
     */
    protected $questionRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Progress
     */
    protected $progressRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\ProgressQuestion
     */
    protected $progressQuestionRepository;

    /**
     * @var \Hondros\Api\Model\Repository\ModuleAttemptQuestion
     */
    protected $moduleAttemptQuestionRepository;

    /**
     * @var \Hondros\Api\Service\Progress
     */
    protected $progressService;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\ModuleAttempt $repository, 
        Repository\Enrollment $enrollmentRepository, Repository\Module $moduleRepository, Repository\Question $questionRepository,
        Repository\Progress $progressRepository, Repository\ProgressQuestion $progressQuestionRepository,
        Repository\ModuleAttemptQuestion $moduleAttemptQuestionRepository, Progress $progressService)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->moduleRepository = $moduleRepository;
        $this->questionRepository = $questionRepository;
        $this->progressRepository = $progressRepository;
        $this->progressQuestionRepository = $progressQuestionRepository;
        $this->moduleAttemptQuestionRepository = $moduleAttemptQuestionRepository;
        $this->progressService = $progressService;
    }
    
    public function findForEnrollmentModule($enrollmentId, $moduleId, $params)
    {
        $paginator = $this->repository->findForEnrollmentModule($enrollmentId, $moduleId, $params);
        
        return new DoctrineCollection($paginator, self::ENTITY_STRATEGY);
    }

    /**
     * @param int $enrollmentId
     * @param int $moduleId
     * @param array $params
     * @return DoctrineSingle
     * @throws Exception
     */
    public function createAttempt($enrollmentId, $moduleId, $params)
    {
        // for now until we find a better way, remove objects
        unset($params['enrollment']);
        unset($params['module']);

        // validate required params
        if (!array_key_exists('quantity', $params)) {
            throw new InvalidArgumentException("Missing required parameter quantity");
        }
        
        if (!array_key_exists('type', $params)) {
            throw new InvalidArgumentException("Missing required parameter type");
        }
        
        $quantity = filter_var($params['quantity'], FILTER_SANITIZE_NUMBER_INT);
        $type = $params['type'];
        $filter = array_key_exists('filter', $params) ? $params['filter'] : 'all';
        
        // validate enrollment
        $enrollment = $this->enrollmentRepository->find($enrollmentId);
        
        if (empty($enrollment)) {
            throw new InvalidArgumentException("Unable to find enrollment {$enrollmentId}");
        }
        
        // validate module
        $module = $this->moduleRepository->find($moduleId);

        if (empty($module)) {
            throw new InvalidArgumentException("Unable to find module {$moduleId}");
        }
        
        // get progress
        try {
            $progress = $this->progressRepository->findByEnrollmentModule($enrollmentId, $moduleId, $type);
        } catch (Exception $e) {
            throw new Exception("Unable to find progress for enrollment {$enrollmentId} module {$moduleId} " . $e->getMessage());
        }
        
        if (empty($progress)) {
            throw new InvalidArgumentException("Unable to find progress for enrollment {$enrollmentId} module {$moduleId}");
        }

        switch ($filter) {
             case 'incorrect':
                // get questions that are marked incorrect
                $progressQuestions = $this->progressQuestionRepository->findByProgressId($progress->getId(), [
                    'answered' => true,
                    'correct' => false,
                    'active' => true
                ]);
                 
                $questionIds = [];
                foreach ($progressQuestions as $progressQuestion) {
                    $questionIds[] = $progressQuestion->getQuestionId();
                }
                break;
            case 'bookmarked':
                // get questions that are marked incorrect
                $progressQuestions = $this->progressQuestionRepository->findByProgressId($progress->getId(), [
                    'progress' => $progress->getId(),
                    'bookmarked' => true,
                    'active' => true
                ]);
                 
                $questionIds = [];
                foreach ($progressQuestions as $progressQuestion) {
                    $questionIds[] = $progressQuestion->getQuestionId();
                }
                break;
              
            case 'unseen':
                // get questions that are not viewed
                $progressQuestions = $this->progressQuestionRepository->findByProgressId($progress->getId(), [
                    'progress' => $progress->getId(),
                    'viewed' => 0,
                    'active' => true
                ]);
                 
                $questionIds = [];
                foreach ($progressQuestions as $progressQuestion) {
                    $questionIds[] = $progressQuestion->getQuestionId();
                }
                break;
                
            default: // all
                // load ordered by viewed the least and are active
                $progressQuestions = $this->progressQuestionRepository->findByProgressId($progress->getId(), [
                    'progress' => $progress->getId(),
                    'active' => true
                ],['viewed' => 'ASC']);
                 
                $questionIds = [];
                foreach ($progressQuestions as $progressQuestion) {
                    $questionIds[] = $progressQuestion->getQuestionId();
                }

                // for this case remove all the extras as we only want the top x results
                if (is_numeric($quantity)) {
                    $questionIds = array_slice($questionIds, 0, $quantity);
                }
                break;
        }
        
        // randomize
        shuffle($questionIds);
        
        // make sure we only keep the right quantity
        if (is_numeric($quantity)) {
            $questionIds = array_slice($questionIds, 0, $quantity);
        }
        
        // create attempt
        $moduleAttempt = new Entity\ModuleAttempt();
        $moduleAttempt->setEnrollment($enrollment)
            ->setEnrollmentId($enrollment->getId())
            ->setModule($module)
            ->setModuleId($module->getId())
            ->setCreated(new \DateTime())
            ->setQuestionCount(count($questionIds))
            ->setType($type);
        
        $this->entityManager->persist($moduleAttempt);
        
        // create all question attempts
        $sort = 0;
        foreach ($questionIds as $questionId) {
            $moduleAttemptQuestion = new Entity\ModuleAttemptQuestion();
            $moduleAttemptQuestion->setModuleAttempt($moduleAttempt)
                ->setQuestion($this->entityManager->getReference('Hondros\Api\Model\Entity\Question', $questionId))
                ->setSort(++$sort)
                ->setCreated(new \DateTime());
            
            $this->entityManager->persist($moduleAttemptQuestion);
        }
        
        // save
        $this->entityManager->flush();
        
        // return the module attempt info, with questions and answers
        return new DoctrineSingle($moduleAttempt, self::ENTITY_STRATEGY);
        
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

        return $this->createAttempt($params['enrollmentId'], $params['moduleId'], $params);
    }

    /**
     * every time we save a module attempt, we reset the progress for that module to make 
     * sure it matches correctly.
     *
     * @param int $id
     * @param array $params
     * @return \Hondros\Common\DoctrineSingle
     */
    public function update($id, $params)
    {
        if (empty($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid id.", 400);
        }

        unset($params['enrollment']);
        unset($params['module']);

        // get module and see what it's current stats are
        $moduleAttempt = $this->repository->find($params['id']);
        
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\ModuleAttempt();
        $hydrator = $strategy->getHydrator();
        $moduleAttempt = $hydrator->hydrate($params, $moduleAttempt);
        $moduleAttempt->setModified(new \DateTime());
    
        $this->entityManager->persist($moduleAttempt);
        
        // update progress as well
        $progress = $this->progressRepository->findByEnrollmentModule($params['enrollmentId'], $params['moduleId'], $params['type']);
        $progress->setAttempts($progress->getAttempts() + 1);

        $correct = 0;
        $incorrect = 0;
        $bookmarked = 0;
        $score = $moduleAttempt->getScore();
        $questions = $this->progressQuestionRepository->findByProgressId($progress->getId(), ['active' => true]);
        foreach ($questions as $question) {
            if (!$question->getAnswered()) {
                continue;
            }

            if ($question->getCorrect()) {
                $correct++;
            } else {
                $incorrect++;
            }

            if ($question->getBookmarked()) {
                $bookmarked++;
            }
        }

        if ($params['type'] == 'practice' && $moduleAttempt->getScore() >= self::SCORE_CUT_OFF) {
            $score = ceil($progress->getScore() + ($moduleAttempt->getScore() * Entity\Progress::PRACTICE_SCORE_MULTIPLIER));
            if ($score > 100) {
                $score = 100;
            }
        } elseif ($params['type'] == 'study') {
            $score = ceil($correct / $progress->getQuestionCount() * 100);
        }

        $progress->setCorrect($correct)
            ->setIncorrect($incorrect)
            ->setBookmarked($bookmarked)
            ->setScore($score)
            ->setModified(new \DateTime());

        $this->entityManager->flush();
    
        return new DoctrineSingle($moduleAttempt, self::ENTITY_STRATEGY);
    }

    public function recalculateBasedOnModuleAttemptQuestions($id)
    {
        $moduleAttempt = $this->repository->findOneById($id);
        $moduleAttemptQuestions = $this->moduleAttemptQuestionRepository->findFor("moduleAttempt", $id);

        // counters
        $questionCount = 0;
        $correct = 0;
        $incorrect = 0;
        $bookmarked = 0;
        $score = 0;

        foreach ($moduleAttemptQuestions as $moduleAttemptQuestion) {
            // track questions
            $questionCount++;

            // bookmarked?
            if ($moduleAttemptQuestion->getBookmarked()) {
                $bookmarked++;
            }

            // if not answered it can't be correct/incorrect
            if ($moduleAttemptQuestion->getAnswered() && $moduleAttemptQuestion->getCorrect()) {
                $correct++;
            }

            if ($moduleAttemptQuestion->getAnswered() && !$moduleAttemptQuestion->getCorrect()) {
                $incorrect++;
            }
        }

        $score = ($correct / $questionCount) * 100;

        // update progress
        $moduleAttempt->setQuestionCount($questionCount);
        $moduleAttempt->setCorrect($correct);
        $moduleAttempt->setIncorrect($incorrect);
        $moduleAttempt->setBookmarked($bookmarked);
        $moduleAttempt->setScore($score > 100 ? 100 : ceil($score));
        $moduleAttempt->setModified(new \DateTime());

        // save if anything changed
        $this->entityManager->flush();

        return new DoctrineSingle($moduleAttempt, self::ENTITY_STRATEGY);
    }
}
