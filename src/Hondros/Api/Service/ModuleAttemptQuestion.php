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

class ModuleAttemptQuestion extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\ModuleAttemptQuestion';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'ModuleAttemptQuestion';
    
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
     * @var \Hondros\Api\Model\Repository\ProgressQuestion
     */
    protected $progressQuestionRepository;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\ModuleAttemptQuestion $repository, 
        Repository\ProgressQuestion $progressQuestionRepository) 
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->progressQuestionRepository = $progressQuestionRepository;
    }
    
    public function findLatestForEnrollmentModule($enrollmentId, $moduleId, $params)
    {
        $collection = $this->repository->findLatestForEnrollmentModule($enrollmentId, $moduleId, $params);
    
        return new DoctrineCollection($collection, self::ENTITY_STRATEGY);
    }
    
    public function save($params)
    {
        // for now until we find a better way, remove objects
        unset($params['question']);
        unset($params['moduleAttempt']);
        
        $moduleQuestionAttempt = $this->repository->find($params['id']);
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\ModuleAttemptQuestion();
        $hydrator = $strategy->getHydrator();
        $moduleQuestionAttempt = $hydrator->hydrate($params, $moduleQuestionAttempt);
        $moduleQuestionAttempt->setModified(new \DateTime());
        
        $this->entityManager->persist($moduleQuestionAttempt);
        $this->entityManager->flush();
        
        return new DoctrineSingle($moduleQuestionAttempt, self::ENTITY_STRATEGY);
    }
    
    /**
     * Takes in a collection of module attempt questions and updates them all
     *
     * For efficiency and performance, we want to also update the progress questions if attached
     *
     * @param array $data
     * @return \Hondros\Common\DoctrineCollection
     */
    public function updateBulk($data)
    {
        $collection = [];
        $progressQuestionsCollection = [];
    
        if (empty($data) || !is_array($data[0])) {
            throw new InvalidArgumentException("Data must be an array for bulk updates.");
        }
    
        // now loop, clean up, and get ids
        foreach ($data as &$row) {
            // for now until we find a better way, remove objects - need to fix the hydrator logic
            unset($row['question']);
            unset($row['moduleAttempt']);
    
            // if progress is there, track and remove
            if (!empty($row['progressQuestion'])) {
                $progressQuestionsCollection[$row['progressQuestion']['id']] = $row['progressQuestion'];
                unset($row['progressQuestion']);
            }
            
            $collection[$row['id']] = $row;
        }
        
        // don't need data anymore
        unset($data);
    
        // load all module attempt questions and progress questions
        // needed for validation to make sure they really are there but slower as we need to select
        $moduleAttemptQuestions = $this->repository->findById(array_keys($collection));
        $progressQuestions = $this->progressQuestionRepository->findById(array_keys($progressQuestionsCollection)); 
        
        // setup for module attempt questionss
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\ModuleAttemptQuestion();
        $hydrator = $strategy->getHydrator();
    
        foreach ($moduleAttemptQuestions as $moduleAttemptQuestion) {
            $moduleAttemptQuestion = $hydrator->hydrate($collection[$moduleAttemptQuestion->getId()], $moduleAttemptQuestion);
            $moduleAttemptQuestion->setModified(new \DateTime());
            $this->entityManager->persist($moduleAttemptQuestion);
        }
        
        // setup for module attempt questionss
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\ProgressQuestion();
        $hydrator = $strategy->getHydrator();
    
        foreach ($progressQuestions as $progressQuestion) {
            $progressQuestion = $hydrator->hydrate($progressQuestionsCollection[$progressQuestion->getId()], $progressQuestion);
            $this->entityManager->persist($progressQuestion);
        }

        // update all
        $this->entityManager->flush();
    
        return new DoctrineCollection($moduleAttemptQuestions, self::ENTITY_STRATEGY);
    }
}
