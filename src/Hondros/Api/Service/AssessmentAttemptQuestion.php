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

class AssessmentAttemptQuestion extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\AssessmentAttemptQuestion';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'AssessmentAttemptQuestion';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\AssessmentAttemptQuestion
     */
    protected $repository;
    
    /**
     * @var \Hondros\Api\Model\Repository\AssessmentAttempt
     */
    protected $assessmentAttemptRepository;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\AssessmentAttemptQuestion $repository,
        Repository\AssessmentAttempt $assessmentAttemptRepository) 
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->assessmentAttemptRepository = $assessmentAttemptRepository;
    }
    
    public function save($params)
    {
        // for now until we find a better way, remove objects
        unset($params['question']);
        unset($params['assessmentAttempt']);
        unset($params['module']);
        
        $assessmentQuestionAttempt = $this->repository->find($params['id']);
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\AssessmentAttemptQuestion();
        $hydrator = $strategy->getHydrator();
        $assessmentQuestionAttempt = $hydrator->hydrate($params, $assessmentQuestionAttempt);
        $assessmentQuestionAttempt->setModified(new \DateTime());
        
        $this->entityManager->persist($assessmentQuestionAttempt);
        $this->entityManager->flush();
        
        return new DoctrineSingle($assessmentQuestionAttempt, self::ENTITY_STRATEGY);
    }

    public function update($id, $params)
    {
        if (empty($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid assessment attempt question id.", 400);
        }

        // get question
        $question = $this->repository->find($id);

        if (empty($question)) {
            throw new \InvalidArgumentException("Invalid assessment attempt question id.", 400);
        }

        // clean up data
        unset($params['question']);
        unset($params['assessmentAttempt']);
        unset($params['module']);

        // hydrate new data
        $hydrator = (new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\AssessmentAttemptQuestion())
            ->getHydrator();

        $hydrator->hydrate($params, $question);

        // save
        $question->setModified(new \DateTime());
        $this->entityManager->flush();

        // done
        return new DoctrineSingle($question, self::ENTITY_STRATEGY);
    }
    
    /**
     * Takes in a collection of assessment attempt questions and updates them all
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
            unset($row['question']);
            unset($row['assessmentAttempt']);
            unset($row['module']);
    
            $collection[$row['id']] = $row;
        }
    
        // don't need data anymore
        unset($data);
    
        // needed for validation to make sure they really are there but slower as we need to select
        $assessmentAttemptQuestions = $this->repository->findById(array_keys($collection));
    
        // setup
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\AssessmentAttemptQuestion();
        $hydrator = $strategy->getHydrator();
    
        foreach ($assessmentAttemptQuestions as $assessmentAttemptQuestion) {
            $assessmentAttemptQuestion = $hydrator->hydrate($collection[$assessmentAttemptQuestion->getId()], $assessmentAttemptQuestion);
            $assessmentAttemptQuestion->setModified(new \DateTime());
            $this->entityManager->persist($assessmentAttemptQuestion);
        }
        
        // also update the assessment attempt
        $assessmentAttemptQuestion->setModified(new \DateTime());
    
        // update all
        $this->entityManager->flush();
    
        return new DoctrineCollection($assessmentAttemptQuestions, self::ENTITY_STRATEGY);
    }
}
