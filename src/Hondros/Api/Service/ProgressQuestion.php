<?php

namespace Hondros\Api\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Hondros\Api\Service\ServiceAbstract;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;

class ProgressQuestion extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\ProgressQuestion';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'ProgressQuestion';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\ProgressQuestion
     */
    protected $repository;
    
    /**
     * @var \Hondros\Api\Model\Repository\ModuleAttemptQuestion
     */
    protected $moduleAttemptQuestionRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Module
     */
    protected $moduleRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Progress
     */
    protected $progressRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Question
     */
    protected $questionRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\ModuleAttempt
     */
    protected $moduleAttemptRepository;
    
    public function __construct(EntityManager $entityManager, Logger $logger, 
        Repository\ProgressQuestion $repository, Repository\ModuleAttemptQuestion $moduleAttemptQuestionRepository, 
        Repository\Module $moduleRepository, Repository\Progress $progressRepository, Repository\Question $questionRepository,
        Repository\ModuleAttempt $moduleAttemptRepository) 
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->moduleAttemptQuestionRepository = $moduleAttemptQuestionRepository;
        $this->moduleRepository = $moduleRepository;
        $this->progressRepository = $progressRepository;
        $this->questionRepository = $questionRepository;
        $this->moduleAttemptRepository = $moduleAttemptRepository;
    }
    
    /**
     * @param int $moduleAttemptId
     * @return DoctrineCollection
     */
    public function findForModuleAttempt($moduleAttemptId)
    {
        // get ids
        $ids = $this->moduleAttemptQuestionRepository->findQuestionIdsForModuleAttempt($moduleAttemptId);
        $questionIds = [];
        
        // flatten the ids
        array_walk($ids, function ($item, $key) use (&$questionIds) {
            $questionIds[] = $item['questionId'];
        });
        
        // need to figure out the progress id
        $moduleAttempt = $this->moduleAttemptRepository->find($moduleAttemptId);
        $progress = $this->progressRepository->findByEnrollmentModule($moduleAttempt->getEnrollmentId(), $moduleAttempt->getModuleId(), $moduleAttempt->getType());
        
        // now load progress questions
        //$paginator = $this->repository->findByProgressIdAndQuestionIdsWithQuestionAndAnswers($progress->getId(), $questionIds);

        $progressQuestions = $this->repository->findBy([
            'progress' => $progress->getId(),
            'question' => $questionIds
        ]);

        $questions = $this->questionRepository->findByIdsWithAnswers($questionIds);

        foreach ($progressQuestions as $progressQuestion) {
            foreach ($questions as $question) {
                if ($progressQuestion->getQuestionId() === $question->getId()) {
                    $progressQuestion->setQuestion($question);
                    break;
                }
            }
        }

        $collection = new DoctrineCollection($progressQuestions, self::ENTITY_STRATEGY, ['question', 'question.answers']);
        $collection->setTotal(count($progressQuestions));

        return $collection;
    }
    
    /**
     * specific to the exam prep app to limit number of api calls
     *
     * @todo when possible, remove this and update app to make two separate calls
     * @param int $progressId
     * @param array $params
     * @return DoctrineCollection
     */
    public function findForProgressDetails($progressId, $params)
    {
        // make sure we only get active questions
        $params['active'] = true;
        
        // get all progress questions // pass in pagination info
        $progressQuestions = $this->repository->findByProgressId($progressId, $params);
        $total = count($progressQuestions);

        // do we have anything?
        if (!$total) {
            return new DoctrineCollection([]);
        }

        // remove params for prior call so it doesn't mess with it's cache
        if (isset($params['bookmarked'])) {
            unset($params['bookmarked']);
        }

        $progressQuestions = $progressQuestions->getQuery()->getResult();

        $questionIds = [];
        /** @var Entity\ProgressQuestion $progressQuestion */
        foreach ($progressQuestions as $progressQuestion) {
            $questionIds[] = $progressQuestion->getQuestionId();
        }

        // add hydrated questions to progress questions
        $questions = $this->questionRepository->findByIdsWithAnswers($questionIds);
        foreach ($progressQuestions as $progressQuestion) {
            foreach ($questions as $question) {
                if ($progressQuestion->getQuestionId() === $question->getId()) {
                    $progressQuestion->setQuestion($question);
                    break;
                }
            }
        }

        $collection = new DoctrineCollection($progressQuestions, self::ENTITY_STRATEGY, ['question', 'question.answers']);
        $collection->setTotal($total);
        
        if (!empty($params['pageSize'])) {
            $collection->setLimit($params['pageSize']);
        }
        
        if (!empty($params['page']) && !empty($params['pageSize'])) {
            $collection->setOffset(((int)$params['page'] - 1) * (int)$params['pageSize']);
        }
        
        return $collection;
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
        unset($params['question']);
        unset($params['progress']);
    
        // get module and see what it's current stats are
        $progress = $this->repository->find($params['id']);
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Progress();
        $hydrator = $strategy->getHydrator();
        $progress = $hydrator->hydrate($params, $progress);
    
        $this->entityManager->persist($progress);
        $this->entityManager->flush();
    
        return new DoctrineSingle($progress, self::ENTITY_STRATEGY);
    }  
}
