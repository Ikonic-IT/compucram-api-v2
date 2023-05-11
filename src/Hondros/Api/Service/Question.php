<?php

namespace Hondros\Api\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Hondros\Api\Service\ServiceAbstract;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Common\Collection;
use Hondros\Common\DoctrineSingle;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Elasticsearch\Client as ElasticsearchClient;

class Question extends ServiceAbstract
{
    /**
     * @var string
     */
    const ENTITY_PATH = '\Hondros\Api\Model\Entity\Question';
    
    /**
     * @var string
     */
    const ENTITY_STRATEGY = 'Question';
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */   
    protected $entityManager;
    
    /**
    * @var \Monolog\Logger
    */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\Question
     */
    protected $repository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Module
     */
    protected $moduleRepository;

    /**
     * @var Repository\QuestionAudit
     */
    protected $questionAuditRepository;

    /**
     * @var Repository\QuestionBank
     */
    protected $questionBankRepository;

    /**
     * @var \Elasticsearch\Client
     */
    protected $elasticsearch;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\Question $repository,
                                Repository\Module $moduleRepository, ElasticsearchClient $elasticsearch,
                                Repository\QuestionAudit $questionAuditRepository,
                                Repository\QuestionBank $questionBankRepository)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->repository = $repository;
        $this->moduleRepository = $moduleRepository;
        $this->elasticsearch = $elasticsearch;
        $this->questionAuditRepository = $questionAuditRepository;
        $this->questionBankRepository = $questionBankRepository;
    }

    public function update($id, $params)
    {
        if (empty($id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid question id.", 400);
        }

        // get question
        $question = $this->repository->find($id);

        if (empty($question)) {
            throw new \InvalidArgumentException("Invalid question id.", 400);
        }

        // clean up data
        unset($params['questionBank']);
        unset($params['answers']);
        unset($params['createdBy']);
        unset($params['modifiedBy']);

        // hydrate new data
        $hydrator = (new \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Question())
            ->getHydrator();

        $hydrator->hydrate($params, $question);

        // save
        $this->entityManager->flush();

        // done
        return new DoctrineSingle($question, self::ENTITY_STRATEGY);
    }

    /**
     * Allow searching ES by query (using their own query syntax)
     *
     * @todo consider security implications of passing in a ES query directly
     * @todo add integration tests
     * @docs https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_search_operations.html
     * @param $params
     * @return DoctrineCollection
     */
    public function search($params)
    {
        // validate params based on some criteria

        // handle special mapping - just _id => id for now
//        foreach ($params['query']['match'] as $key => $value) {
//            if ($key === 'id') {
//                $params['query']['match']['_id'] = $value;
//                unset($params['query']['match'][$key]);
//            }
//        }

        if (empty($params['query'])) {
            throw new \InvalidArgumentException("Invalid query.");
        }

        $criteria = [
            'index' => 'questions',
            'type' => 'question',
            'body' => [
                'query' => $params['query'],
            ],
            '_source' => false
        ];

        $criteria['size'] = 10;
        if (!empty($params['size'])) {
            $criteria['size'] = $params['size'];
        }

        $criteria['from'] = 0;
        if (!empty($params['page'])) {
            $criteria['from'] = ((int)$params['page'] - 1) * $criteria['size'];
        }

        $results = $this->elasticsearch->search($criteria);
        // did we find any?
        if (!$results['hits']['total']['value']) {
            return new Collection([]);
        }

        $ids = [];
        array_walk($results['hits']['hits'], function ($value, $index) use (&$ids) {
            $ids[] = $value['_id'];
        });

        $questions = $this->repository->findByIdsWithAnswers($ids);

        if (empty($questions)) {
            return new Collection([]);
        }

        $orderedQuestions = array_fill(0, count($ids), 0);

        // put in the right order
        foreach ($questions as $key => $question) {
            $orderedQuestions[array_search($question->getId(), $ids)] = $question;
            unset($questions[$key]);
        }

        $collection = new DoctrineCollection($orderedQuestions, self::ENTITY_STRATEGY, ['answers']);
        $collection->setTotal($results['hits']['total']['value']);
        $collection->setLimit($criteria['size']);
        $collection->setOffset($criteria['from']);

        return $collection;
    }

    /**
     * Get audit records for question if any
     *
     * @param int $questionId
     * @return DoctrineCollection
     */
    public function findAudits($questionId)
    {
        if (empty($questionId) || filter_var($questionId, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid question id.", 400);
        }

        // get question
        $question = $this->repository->find($questionId);

        if (empty($question)) {
            throw new \InvalidArgumentException("Question not found.");
        }

        $audits = $this->questionAuditRepository->findByQuestionId($questionId);

        return new DoctrineCollection($audits, 'QuestionAudit');
    }

    /**
     * Use this method for getting questions since it uses caching
     *
     * @param $questionBankId
     * @param array $params
     * @return Paginator
     */
    public function findForQuestionBank($questionBankId, $params = [])
    {
        $includes = !empty($params['includes']) ? $params['includes'] : [];
        $page = !empty($params['page']) ? $params['page'] : 1;
        $limit = !empty($params['pageSize']) ? $params['pageSize'] : 50;
        $offset = $limit * ($page - 1);

        $questions = $this->repository->findForQuestionBank($questionBankId, $includes, $limit, $offset);

//        if ($questions instanceof Paginator) {
//            return new DoctrineCollection($questions, self::ENTITY_STRATEGY, ['answers']);
//        }

        return $questions;
    }

    /**
     * Get all questions and answers for a module can filter by type
     *
     * @param int $moduleId
     * @param string|null $type
     * @param array $params
     * @return DoctrineCollection
     */
    public function findForModule($moduleId, $type = null, $params = [])
    {
        $page = !empty($params['page']) ? $params['page'] : 1;
        $limit = !empty($params['pageSize']) ? $params['pageSize'] : 50;
        $offset = $limit * ($page - 1);

        $questions = $this->repository->findForModule($moduleId, $type, $limit, $offset);

        $collection = new DoctrineCollection($questions, 'Question', ['answers']);
        $collection->setTotal($this->repository->findForModuleCount($moduleId, $type));

        if (!empty($limit)) {
            $collection->setLimit($limit);
        }

        if (!empty($offset)) {
            $collection->setOffset($offset);
        }

        return $collection;
    }

    public function save($params)
    {
        // create new
        if (empty($params['id'])) {
            return $this->createNew($params);
        }
    }

    /**
     * @param array $params
     * @return DoctrineSingle
     */
    protected function createNew($params)
    {
        if (empty($params['type'])) {
            throw new InvalidArgumentException("Invalid type.", 400);
        }

        if (empty($params['questionText'])) {
            throw new InvalidArgumentException("Invalid question text.", 400);
        }

        if (!isset($params['active'])) {
            throw new InvalidArgumentException("Invalid active.", 400);
        }

        if (empty($params['answers'])) {
            throw new InvalidArgumentException("Invalid answers.", 400);
        }

        if (!isset($params['correctAnswerIndex'])) {
            throw new InvalidArgumentException("Invalid answer index.", 400);
        }

        $questionBank = null;
        if (!empty($params['questionBankId'])) {
            // make sure the question bank exists
            // @todo remove after new schema module question is releases
            $questionBank = $this->questionBankRepository->findOneBy(['id' => $params['questionBankId']]);

            if (empty($questionBank)) {
                throw new InvalidArgumentException("Question bank not found.", 400);
            }
        }

        $question = $this->repository->createNew(
            $params['type'],
            !is_null($questionBank) ? $questionBank : null,
            $params['questionText'],
            $params['answers'],
            $params['correctAnswerIndex'],
            isset($params['feedback']) ? $params['feedback'] : null,
            isset($params['active']) ? (bool) $params['active']: null
        );

        // return the module attempt info, with questions and answers
        return new DoctrineSingle($question, self::ENTITY_STRATEGY);
    }
}