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

class Exam extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\Exam';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'Exam';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\Enrollment
     */
    protected $repository;

    /**
     * Exam constructor.
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param Repository\Exam $repository
     */
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\Exam $repository) 
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
    }

    /**
     * @param string $code
     * @param array $params
     * @return DoctrineSingle
     */
    public function findByCode($code, $params = [])
    {
        $exam = $this->repository->findOneByCode($code);
    
        if (empty($exam)) {
            throw new InvalidArgumentException("No exam found", 404);
        }
    
        return new DoctrineSingle($exam, self::ENTITY_STRATEGY);
    }

    /**
     * @param int $examId
     * @param array $params
     * @return DoctrineSingle
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update($examId, $params)
    {
        if (empty($examId) || filter_var($examId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid exam id {$examId}", 400);
        }

        $exam = $this->repository->find($examId);

        if (empty($exam)) {
            throw new InvalidArgumentException("Exam not found for {$examId}.", 409);
        }

        // clean up data
        unset($params['industry']);
        unset($params['state']);

        // hydrate new data
        $hydrator = (new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Exam())
            ->getHydrator();
        $hydrator->hydrate($params, $exam);
        $exam->setModified(new \DateTime());

        // save
        $this->entityManager->flush();

        // done
        return new DoctrineSingle($exam, self::ENTITY_STRATEGY);
    }
}
