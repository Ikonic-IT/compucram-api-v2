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

class Answer extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\Answer';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'Answer';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\Answer
     */
    protected $repository;

    /**
     * @var Repository\AnswerAudit
     */
    protected $answerAuditRepository;

    public function __construct(EntityManager $entityManager, Logger $logger, Repository\Answer $repository,
        Repository\AnswerAudit $answerAuditRepository)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->answerAuditRepository = $answerAuditRepository;
    }

    /**
     * @todo validate question/answer
     * @todo don't update if nothing changed
     * @param $questionId
     * @param $data
     * @return DoctrineCollection
     */
    public function updateBulk($data)
    {
        $collection = [];

        if (empty($data) || !is_array($data[0])) {
            throw new InvalidArgumentException("Data must be an array for bulk updates.");
        }

        // now loop, clean up, and get ids
        foreach ($data as &$row) {
            unset($row['question']);
            unset($row['createdBy']);
            unset($row['modifiedBy']);
            $collection[$row['id']] = $row;
        }

        // don't need data anymore
        unset($data);

        // needed for validation to make sure they really are there but slower as we need to select
        $progresses = $this->repository->findById(array_keys($collection));

        // validate that the answers belong to this question

        // setup
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Answer();
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

    public function findAudits($questionId)
    {
        return new DoctrineCollection($this->answerAuditRepository->findByQuestionId($questionId), 'AnswerAudit');
    }
}