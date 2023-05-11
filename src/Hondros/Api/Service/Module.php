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

class Module extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\Module';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'Module';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\Module
     */
    protected $repository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Question
     */
    protected $questionRepository;

    /**
     * Module constructor.
     * @param EntityManager $entityManager
     * @param Logger $logger
     * @param Repository\Module $repository
     * @param Repository\Question $questionRepository
     */
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\Module $repository,
                                Repository\Question $questionRepository)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->questionRepository = $questionRepository;
    }

    /**
     * @param string $code
     * @param array $params
     * @return DoctrineSingle
     */
    public function findByCode($code, $params = [])
    {
        $module = $this->repository->findOneByCode($code);

        if (empty($module)) {
            throw new InvalidArgumentException("No module found", 404);
        }

        return new DoctrineSingle($module, self::ENTITY_STRATEGY);
    }

    /**
     * Adds a question to a module type.
     *
     * Note: it doens't create a question, assumes the question already exists
     *
     * @param int $moduleId
     * @param string $moduleType
     * @param $questionId
     * @return DoctrineSingle
     * @throws \InvalidArgumentException|\Exception
     */
    public function addQuestion($moduleId, $moduleType, $questionId)
    {
        if (empty($moduleId) || filter_var($moduleId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid module id.", 400);
        }

        if (empty($moduleType)
            || filter_var($moduleType, FILTER_SANITIZE_STRING) === false
            || !in_array($moduleType, Entity\ModuleQuestion::getValidTypes())) {
            throw new InvalidArgumentException("Invalid module type.", 400);
        }

        if (empty($questionId) || filter_var($questionId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid question id.", 400);
        }

        $module = $this->repository->find($moduleId);
        if (empty($module)) {
            throw new InvalidArgumentException("No module found.", 400);
        }

        $question = $this->questionRepository->find($questionId);
        if (empty($question)) {
            throw new InvalidArgumentException("No question found.", 400);
        }

        $moduleQuestion = (new Entity\ModuleQuestion())
            ->setModuleId($moduleId)
            ->setModule($this->entityManager->getReference(Entity\Module::class, $module->getId()))
            ->setType($moduleType)
            ->setQuestionId($questionId)
            ->setQuestion($this->entityManager->getReference(Entity\Question::class, $question->getId()));
        $this->entityManager->persist($moduleQuestion);

        try {
            $this->entityManager->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $this->logger->info("duplicate key insert error for module {$moduleId} type {$moduleType} and "
                . "question {$questionId}.");
            throw new \InvalidArgumentException("This question already exists.", 400);
        } catch (\Exception $e) {
            $this->logger->error("Exception adding question for module {$moduleId} type {$moduleType} and "
                . "question {$questionId}. " . $e->getMessage());
            throw new \Exception("Unknown error. Contact support for more information.", 500);
        }

        return new DoctrineSingle($moduleQuestion, 'ModuleQuestion', ['module', 'question']);
    }

    /**
     * @param int $moduleId
     * @param array $params
     * @return DoctrineSingle
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update($moduleId, $params) {
        if (empty($moduleId) || filter_var($moduleId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid module id {$moduleId}", 400);
        }

        $module = $this->repository->find($moduleId);

        if (empty($module)) {
            throw new InvalidArgumentException("Module not found for {$moduleId}.", 409);
        }

        // clean up data
        unset($params['industry']);
        unset($params['state']);

        // hydrate new data
        $hydrator = (new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Module())
            ->getHydrator();
        $hydrator->hydrate($params, $module);
        $module->setModified(new \DateTime());

        // save
        $this->entityManager->flush();

        // done
        return new DoctrineSingle($module, self::ENTITY_STRATEGY);
    }
}