<?php

namespace Hondros\Api\Service;

use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use DateTime;

class ExamModule extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\ExamModule';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'ExamModule';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\ExamModule
     */
    protected $repository;

    /**
     * @var \Hondros\Api\Model\Repository\Exam
     */
    protected $examRepository;

    /**
     * @var \Hondros\Api\Model\Repository\Module
     */
    protected $moduleRepository;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\ExamModule $repository,
        Repository\Exam $examRepository, Repository\Module $moduleRepository)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->examRepository = $examRepository;
        $this->moduleRepository = $moduleRepository;
    }

    public function save($examId, $moduleId, $params)
    {
        // create new
        if (empty($params['id'])) {
            return $this->createNew($examId, $moduleId, $params);
        }
    }

    /**
     * @param int $examId
     * @param int $moduleId
     * @param array $params
     * @return DoctrineSingle
     */
    protected function createNew($examId, $moduleId, $params)
    {
        if (empty($examId) || filter_var($examId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid exam id {$examId}", 400);
        }

        if (empty($moduleId) || filter_var($moduleId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid module id {$moduleId}", 400);
        }

        if (empty($params['name'])) {
            throw new InvalidArgumentException("Name is required", 400);
        }

        if (empty($params['preassessmentQuestions']) || filter_var($params['preassessmentQuestions'], FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid preassessment questions", 400);
        }

        if (empty($params['practiceQuestions']) || filter_var($params['practiceQuestions'], FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid practice questions", 400);
        }

        if (empty($params['examQuestions']) || filter_var($params['examQuestions'], FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid exam questions", 400);
        }

        if (empty($params['sort']) || filter_var($params['sort'], FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid sort", 400);
        }

        // verify the exam is valid
        $exam = $this->examRepository->find($examId);
        if (empty($exam)) {
            throw new InvalidArgumentException("Exam not found for id {$examId}.", 409);
        }

        // verify the module is valid
        $module = $this->moduleRepository->find($moduleId);
        if (empty($module)) {
            throw new InvalidArgumentException("Module not found for id {$moduleId}.", 409);
        }

        $dateTime = new DateTime();
        $examModule = (new Entity\ExamModule())
            ->setExam($this->entityManager->getReference('Hondros\Api\Model\Entity\Exam', $examId))
            ->setExamId($examId)
            ->setModule($this->entityManager->getReference('Hondros\Api\Model\Entity\Module', $moduleId))
            ->setModuleId($moduleId)
            ->setName($params['name'])
            ->setPreassessmentQuestions($params['preassessmentQuestions'])
            ->setPracticeQuestions($params['practiceQuestions'])
            ->setExamQuestions($params['examQuestions'])
            ->setSort($params['sort'])
            ->setCreated($dateTime)
            ->setModified($dateTime);

        $this->entityManager->persist($examModule);
        $this->entityManager->flush();

        return new DoctrineSingle($examModule, self::ENTITY_STRATEGY);
    }

    /**
     * @param int $examId
     * @param int $moduleId
     * @param array $params
     * @return DoctrineSingle
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update($examId, $moduleId, $params) {
        if (empty($examId) || filter_var($examId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid exam id {$examId}", 400);
        }

        if (empty($moduleId) || filter_var($moduleId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid module id {$moduleId}", 400);
        }

        $examModule = $this->repository->findOneBy([
            'exam' => $examId,
            'module' => $moduleId
        ]);

        if (empty($examModule)) {
            throw new InvalidArgumentException("ExamModule not found for {$examId} {$moduleId}", 409);
        }

        // clean up data
        unset($params['exam']);
        unset($params['module']);

        // hydrate new data
        $hydrator = (new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\ExamModule())
            ->getHydrator();
        $hydrator->hydrate($params, $examModule);
        $examModule->setModified(new DateTime());

        // save
        $this->entityManager->flush();

        // done
        return new DoctrineSingle($examModule, self::ENTITY_STRATEGY);
    }

    /**
     * Bulk update of multiple exam modules
     * @param int $examId
     * @param array $data
     * @return DoctrineCollection
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateBulk($examId, $data)
    {
        $collection = [];

        if (empty($data)) {
            throw new InvalidArgumentException("Data must be an array for bulk updates.");
        }

        // now loop, clean up, and get ids
        foreach ($data as &$row) {
            if (!is_array($row)) {
                throw new InvalidArgumentException("Invalid data passed.");
            }

            // for now until we find a better way, remove objects - need to fix the hydrator logic
            unset($row['exam']);
            unset($row['module']);

            $collection[$row['id']] = $row;
        }

        // don't need data anymore
        unset($data);

        // needed for validation to make sure they really are there but slower as we need to select
        $examModules = $this->repository->findById(array_keys($collection));

        // setup
        $strategy = new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\ExamModule();
        $hydrator = $strategy->getHydrator();

        foreach ($examModules as $examModule) {
            // make sure they belong to the correct exam
            if ($examModule->getExamId() !== (int)$examId) {
                throw new InvalidArgumentException("These exam modules {$examModule->getExamId()} don't belong to the correct exam {$examId}", 400);
            }

            $examModule = $hydrator->hydrate($collection[$examModule->getId()], $examModule);
            $examModule->setModified(new \DateTime());
            $this->entityManager->persist($examModule);
        }

        // update all
        $this->entityManager->flush();

        return new DoctrineCollection($examModules, self::ENTITY_STRATEGY);
    }

    /**
     * @param int $examId
     * @param int $moduleId
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete($examId, $moduleId)
    {
        if (empty($examId) || filter_var($examId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid exam id {$examId}", 400);
        }

        if (empty($moduleId) || filter_var($moduleId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid module id {$moduleId}", 400);
        }

        $examModule = $this->repository->findOneBy([
            'exam' => $examId,
            'module' => $moduleId
        ]);

        if (empty($examModule)) {
            throw new InvalidArgumentException("ExamModule not found for {$examId} {$moduleId}", 409);
        }

        $this->entityManager->remove($examModule);
        $this->entityManager->flush();

        return true;
    }
}
