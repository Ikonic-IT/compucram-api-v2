<?php

namespace Hondros\Api\Model\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use Doctrine\ORM\Query;
use Hondros\Common\DoctrineCollection;
use Monolog\Logger;
use Predis\Client as Redis;
use Laminas\Stdlib\Hydrator;
use Laminas\Config\Config;
use Doctrine\ORM\Query\ResultSetMapping;
use Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy;
use Hondros\Api\Model\Entity;
use DateTime;

class Question extends RepositoryAbstract
{   
    /**
     * @var boolean if true, caching of this entity will be enabled.
     * @depricated need to depricate this used by old way.
     */
    const CACHE_ENABLED = false;
    
    /**
     * @var int time in seconds for cache to expire
     */
    const CACHE_TTL = 28800; // 8 hours
    
    /**
     * @var string cache key to identify this repo's caches
     */
    const CACHE_ID = 'entity:question:';

    /**
    * @var \Monolog\Logger
    */
    protected $logger;

    /**
     * @var Redis
     */
    protected $redis;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Question
     */
    protected $questionHydratorStrategy;

    /**
     * @var \Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\Answer
     */
    protected $answerHydratorStrategy;

    /**
     * @var QuestionBank
     */
    protected $questionBankRepository;

    /**
     * Question constructor.
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     * @param Logger $logger
     * @param Redis $redis
     * @param Config $config
     * @param Strategy\Entity\Question $questionHydratorStrategy
     * @param Strategy\Entity\Answer $answerHydratorStrategy
     * @param QuestionBank $questionBankRepository
     */
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class,
        Logger $logger, Redis $redis, Config $config,
        Strategy\Entity\Question $questionHydratorStrategy, Strategy\Entity\Answer $answerHydratorStrategy,
        QuestionBank $questionBankRepository)
    {
        $this->logger = $logger;
        $this->redis = $redis;
        $this->config = $config;
        $this->questionHydratorStrategy = $questionHydratorStrategy;
        $this->answerHydratorStrategy = $answerHydratorStrategy;
        $this->questionBankRepository = $questionBankRepository;

        parent::__construct($em, $class);
    }

    public function findOverride($id, $includes = [])
    {
        // check cache
        if (($data = $this->getCacheAdapter()->get(self::CACHE_ID . $id))) {
            $hydrator = $this->questionHydratorStrategy->getHydrator();
            return $hydrator->hydrate(json_decode($data, true), new Entity\Question());
        }

        $entities = ['e'];
        $qb = $this->createQueryBuilder('e');

        foreach ($includes as $include) {
            $entities[] = $include;
            $qb->leftJoin("e.{$include}", $include);
        }

        $qb->select($entities)->where("e.id = {$id}");
        $entity = $qb->getQuery()->getOneOrNullResult();

        if (empty($entity)) {
            throw new \Exception("Id not found", 404);
        }

        return $entity;
    }
    
    public function findForModuleAttempt($moduleId, $attemptId, $params = [])
    {
        $query = $this->getEntityManager()->createQuery("
            SELECT maq, q, a
            FROM Hondros\Api\Model\Entity\ModuleAttemptQuestion maq
            INNER JOIN maq.question q
            INNER JOIN q.answers a
            WHERE maq.moduleAttempt = {$attemptId}
            ORDER BY maq.sort
        ");

        $data = $query->getResult();
        
        $questions = [];
        foreach ($data as $item) {
            $questions[] = $item->getQuestion();
        }
        
        return $questions;
    }

    /**
     * Gets all questions and answers for a module can filter by type. If they aren't cached, cache all questions
     * for the module the first time.
     *
     * @param int $moduleId
     * @param string|null $type
     * @param int $limit
     * @param int $offset
     * @return DoctrineCollection
     */
    public function findForModule($moduleId, $type = null, $limit = 200, $offset = 0)
    {
        // what question ids do we need?
        if (!is_null($type)) {
            $allQuestionIds = $this->redis->smembers("set:module:{$moduleId}:type:{$type}:questions");
        } else {
            $keys = [
                "set:module:{$moduleId}:type:" . Entity\ModuleQuestion::TYPE_STUDY . ":questions",
                "set:module:{$moduleId}:type:" . Entity\ModuleQuestion::TYPE_PRACTICE . ":questions",
                "set:module:{$moduleId}:type:" . Entity\ModuleQuestion::TYPE_EXAM . ":questions",
                "set:module:{$moduleId}:type:" . Entity\ModuleQuestion::TYPE_PREASSESSMENT . ":questions"
            ];
            $allQuestionIds = $this->redis->sunion($keys);
        }

        $questionIds = array_slice($allQuestionIds, $offset, $limit);
        if (!empty($allQuestionIds) && !empty($questionIds)) {
            // load all from cache
            return $this->findByIdsWithAnswers($questionIds);
        }

        $dql = "SELECT mq, q, a 
            FROM Hondros\Api\Model\Entity\ModuleQuestion mq
            INNER JOIN mq.question q 
            INNER JOIN q.answers a 
            WHERE mq.module = :moduleId ";

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('moduleId', $moduleId);

        /** @var Entity\ModuleQuestion $moduleQuestions */
        $moduleQuestions = $query->getResult();

        $questions = [];
        /** @var Entity\ModuleQuestion $moduleQuestion */
        foreach ($moduleQuestions as $moduleQuestion) {
            // remove types we don't need
            if (!empty($type) && $moduleQuestion->getType() !== $type) {
                continue;
            }

            $questions[] = $moduleQuestion->getQuestion();
        }

        return array_slice($questions, $offset, $limit);
    }

    /**
     * get totals
     *
     * @todo add cache, check scard to get number of items in set
     * @param int $moduleId
     * @param string|null $type
     * @return int
     */
    public function findForModuleCount($moduleId, $type = null)
    {
        $numberOfQuestions = 0;
        if (!is_null($type)) {
            $numberOfQuestions = $this->redis->scard("set:module:{$moduleId}:type:{$type}:questions");
        } else {
            $numberOfQuestions = $this->redis->scard("set:module:{$moduleId}:type:" . Entity\ModuleQuestion::TYPE_STUDY . ":questions");
            $numberOfQuestions += $this->redis->scard("set:module:{$moduleId}:type:" . Entity\ModuleQuestion::TYPE_PRACTICE . ":questions");
            $numberOfQuestions += $this->redis->scard("set:module:{$moduleId}:type:" . Entity\ModuleQuestion::TYPE_EXAM . ":questions");
            $numberOfQuestions += $this->redis->scard("set:module:{$moduleId}:type:" . Entity\ModuleQuestion::TYPE_PREASSESSMENT . ":questions");
        }

        if (!empty($numberOfQuestions)) {
            return $numberOfQuestions;
        }

        $dql = "
            SELECT count(mq.id) 
            FROM Hondros\Api\Model\Entity\ModuleQuestion mq
            WHERE mq.module = {$moduleId}
        ";

        if (!empty($type)) {
            $dql .= "AND mq.type = '{$type}'";
        }

        $query = $this->getEntityManager()->createQuery($dql);

        return $query->getSingleScalarResult();
    }

    /**
     * Need to update this to make sure all questions are already cached or start the process of caching them all?
     *
     * @todo implement includes or replace with includeAnswers bool. Don't do answer logic if not needed
     * @param $questionBankId
     * @param array $includes
     * @param int $limit
     * @param int $offset
     * @return $this|\Doctrine\ORM\Tools\Pagination\Paginator
     * @throws \Exception
     */
    public function findForQuestionBank($questionBankId, $includes = [], $limit = 200, $offset = 0)
    {
        $questions = [];
        $answers = [];
        $questionKeys = [];
        $answerKeys = [];
        $answerSetKeys = [];

        // check for question ids
        $allQuestionIds = $this->getCacheAdapter()->smembers("set:questionBank:{$questionBankId}:questions");

        if (empty($allQuestionIds)) {
            $this->cacheQuestionsForQuestionBank($questionBankId);
            return $this->findForQuestionBank($questionBankId, $includes, $limit, $offset);
        }

        // how can we guarantee we have all the question ids?

        // grab the ids we need
        $questionIds = array_slice($allQuestionIds, $offset, $limit);

        if (empty($questionIds)) {
            throw new InvalidArgumentException("No questions found.", 400);
        }

        foreach ($questionIds as $questionId) {
            $questionKeys[] = self::CACHE_ID . $questionId;
            $answerSetKeys[] = "set:question:{$questionId}:answers";
        }

        // grab questions and answers from cache
        $questionsData = $this->redis->mget($questionKeys);
        $answerIds = $this->redis->sunion($answerSetKeys);

        foreach ($answerIds as $answerId) {
            $answerKeys[] = Answer::CACHE_ID . $answerId;
        }

        $answersData = $this->redis->mget($answerKeys);

        unset($questionKeys);
        unset($answerKeys);
        unset($answerIds);

        // gather all answers
        foreach ($answersData as $answerData) {
            if (empty($answerData)) {
                throw new \Exception('Unable to find answer cache');
            }
            $answers[] = $this->answerHydratorStrategy->getHydrator()->hydrate(
                json_decode($answerData, true),
                new Entity\Answer()
            );
        }

        unset($answersData);

        // now get questions and add answers
        foreach ($questionsData as $questionData) {
            if (empty($questionData)) {
                $this->cacheQuestionsForQuestionBank($questionBankId);
                return $this->findForQuestionBank($questionBankId, $includes, $limit, $offset);
            }

            $question = $this->questionHydratorStrategy->getHydrator()->hydrate(
                json_decode($questionData, true),
                new Entity\Question()
            );

            foreach ($answers as $answer) {
                if ($answer->getQuestionId() === $question->getId()) {
                    $question->addAnswer($answer);
                    unset($answer);
                }
            }

            $questions[] = $question;
        }

        unset($questionsData);

        // @todo need to return pagination information - do hack until we find a cleaner approach
        $paginator = (new DoctrineCollection($questions, 'Question', ['answers']))
            ->setLimit($limit)
            ->setOffset($offset)
            ->setTotal(count($allQuestionIds));

        return $paginator;
    }

    /**
     * Add all questions of a question bank into cache
     *
     * @param int $questionBankId
     * @return bool
     */
    protected function cacheQuestionsForQuestionBank($questionBankId)
    {
        $questionBank = $this->questionBankRepository->findOneById($questionBankId);

        if (empty($questionBank)) {
            throw new \InvalidArgumentException("Invalid question bank id.");
        }

        // load all questions which puts them all in cache
        $questions = parent::findFor('questionBank', $questionBankId, [
            'includes' => ['answers'],
        ])->getQuery()->execute();

        if (empty($questions)) {
            throw new \InvalidArgumentException("No questions found.");
        }

        // get all ids and add to questionBank set
        $questionIds = [];
        array_walk($questions, function ($item) use (&$questionIds) {
            $questionIds[] = $item->getId();
        });

        // store in set
        $this->getCacheAdapter()->sadd("set:questionBank:{$questionBankId}:questions", $questionIds);

        return true;
    }

    /**
     * @param int $questionBankId
     * @return array
     */
    public function findIdsByQuestionBank($questionBankId)
    {
        $dql = "
            SELECT q.id
            FROM {$this->_entityName} q
            WHERE q.questionBank = :id
                AND q.active = true
        ";
        
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('id', $questionBankId);
        
        return $query->getArrayResult();
    }

    /**
     * the audio service we used stopped working so we have questions without audio files
     *
     * @param int $offset
     * @return array
     */
    public function findForStudyWithoutAudio($offset = 0)
    {
        $dql = "
            SELECT q
            FROM {$this->_entityName} q
            WHERE q.audioHash IS NULL AND 
                qb.type = 'study'  
        ";
        
        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setFirstResult($offset)
            ->setMaxResults(500);
        
        return $query->getResult();
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findByIdsWithAnswers($ids)
    {
        $questions = $this->getCachedQuestionsWithAnswersByIds($ids);

        // keep it simple, if we don't have everything we need, just call the db
        if (!empty($questions) && count($ids) === count($questions)) {
            return $questions;
        }

        $dql = "
            SELECT q, a
            FROM {$this->_entityName} q
            INNER JOIN q.answers a
            WHERE q.id IN (:ids)
        ";

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('ids', $ids);

        return $query->getResult();
    }

    /**
     * Try to get all we need from cache. If we don't have it all, return false
     *
     * @param int[] $questionIds
     * @return array|bool
     */
    protected function getCachedQuestionsWithAnswersByIds($questionIds)
    {
        $questions = [];
        $answers = [];
        $questionKeys = [];
        $answerKeys = [];
        $answerSetKeys = [];

        foreach ($questionIds as $questionId) {
            $questionKeys[] = self::CACHE_ID . $questionId;
            $answerSetKeys[] = "set:question:{$questionId}:answers";
        }

        // grab questions and answers from cache
        $questionsData = $this->redis->mget($questionKeys);

        if (empty($questionsData)) {
            return false;
        }

        $answerIds = $this->redis->sunion($answerSetKeys);

        if (empty($answerIds)) {
            return false;
        }

        foreach ($answerIds as $answerId) {
            $answerKeys[] = Answer::CACHE_ID . $answerId;
        }

        $answersData = $this->redis->mget($answerKeys);

        if (empty($answersData)) {
            return false;
        }

        unset($questionKeys);
        unset($answerKeys);
        unset($answerIds);

        // gather all answers
        foreach ($answersData as $answerData) {
            if (empty($answerData)) {
                return false;
            }

            $answers[] = $this->answerHydratorStrategy->getHydrator()->hydrate(
                json_decode($answerData, true),
                new Entity\Answer()
            );
        }

        unset($answersData);

        // now get questions and add answers
        foreach ($questionsData as $questionData) {
            if (empty($questionData)) {
                return false;
            }

            $question = $this->questionHydratorStrategy->getHydrator()->hydrate(
                json_decode($questionData, true),
                new Entity\Question()
            );

            foreach ($answers as $answer) {
                if ($answer->getQuestionId() === $question->getId()) {
                    $question->addAnswer($answer);
                    unset($answer);
                }
            }

            $questions[] = $question;
        }

        unset($questionsData);

        return $questions;
    }

    /**
     * grab all active question ids for a module and then return a section of those ids
     * back based on the quantity and randomzied.
     *
     * for preassessments we always want the same questions so we limit the ids
     * coming back from the query
     * @todo remove this limit code, this should be part of the import process. think through import and existing content
     * implications
     *
     * @param int $moduleId
     * @param string $type
     * @param int $quantity
     * @param bool $limitQuery
     * @param bool $activeOnly
     * @return array
     */
    public function getRandomQuestionIds($moduleId, $type, $quantity, $limitQuery = false, $activeOnly = true)
    {
        $query = $this->getEntityManager()->createQueryBuilder();
        $query->select('mq')
            ->from(Entity\ModuleQuestion::class, 'mq')
            ->where('mq.module = :moduleId')
            ->andWhere('mq.type = :type')
            ->orderBy('mq.id')
            ->setParameter('moduleId', $moduleId)
            ->setParameter('type', $type);

        if ($activeOnly) {
            $query->innerJoin('mq.question', 'q')
                ->andWhere('q.active = true');
        }

        if ($limitQuery) {
            $query->setMaxResults($quantity);
        }

        $ids = $query->getQuery()->getArrayResult();
        $randomIds = [];
    
        // randomly select the quantity if more returned
        while (count($randomIds) < $quantity && count($ids) > 0) {
            $index = rand(0,count($ids) - 1);
            $randomIds[] = $ids[$index]['questionId'];
            array_splice($ids, $index, 1);
        }
    
        shuffle($randomIds);
    
        return $randomIds;
    }

    /**
     * One method to add questions and answers to the DB
     *
     * @param string $type type of question such as vocab, multi
     * @param \Hondros\Api\Model\Entity\QuestionBank $bank
     * @param string $questionText
     * @param array $answerTexts
     * @param int $correctAnswer index
     * @param string|null $feedback
     * @param bool|null $active
     * @return Entity\Question
     */
    public function createNew($type, $bank, $questionText, $answerTexts, $correctAnswer, $feedback = null,
                              $active = true)
    {
        // don't check for empty as 0 is valid but empty
        if ($questionText == '') {
            throw new InvalidArgumentException("Question cannot be empty.");
        }

        // which answer is correct?
        if (false === filter_var($correctAnswer, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("Unable to identify correct answer choice.");
        }

        if ($correctAnswer < 0 || (($correctAnswer + 1) > count($answerTexts))) {
            throw new InvalidArgumentException("Unable to identify correct answer choice index.");
        }

        // make sure the question bank type and question type are a match
        if (!is_null($bank)) {
            if ($bank->getType() === Entity\QuestionBank::TYPE_STUDY && $type !== Entity\Question::TYPE_VOCAB) {
                throw new InvalidArgumentException("Invalid question type for question bank type.");
            } else if (($bank->getType() === Entity\QuestionBank::TYPE_PRACTICE
                    || $bank->getType() === Entity\QuestionBank::TYPE_EXAM)
                && $type !== Entity\Question::TYPE_MULTI) {
                throw new InvalidArgumentException("Invalid question type for question bank type.");
            }
        }

        // check answers 1 for vocab and 2 to 10 for multi
        if ($type === Entity\Question::TYPE_VOCAB && count($answerTexts) !== 1) {
            throw new InvalidArgumentException("Wrong number of answers for question type.");
        } else if ($type === Entity\Question::TYPE_MULTI &&
            (count($answerTexts) < 2 || count($answerTexts) > 10)) {
            throw new InvalidArgumentException("Wrong number of answers for question type.");
        }

        $date = new DateTime();
        $question = new Entity\Question();
        $question->setType($type)
            ->setQuestionText($questionText)
            ->setFeedback($feedback)
            ->setActive($active)
            ->setCreated($date);

        if (!is_null($bank)) {
            $question->setQuestionBank($bank);
        }

        $this->getEntityManager()->persist($question);

        // add answers
        $answers = [];
        for ($x = 0, $max = count($answerTexts); $x < $max; $x++) {
            if ($answerTexts[$x] == '') {
                throw new InvalidArgumentException("Answer text cannot be empty.");
            }

            $answer = new Entity\Answer();
            $answer->setQuestion($question)
                ->setAnswerText($answerTexts[$x])
                ->setCorrect($x === $correctAnswer)
                ->setCreated($date);
            $this->getEntityManager()->persist($answer);

            // track it
            $answers[] = $answer;
        }

        // need at least 1 answer
        if (count($answers) < 1) {
            throw new InvalidArgumentException("Unable to add at least 1 answer");
        }

        $this->getEntityManager()->flush();

        return $question;
    }

    /**
     * Check if there is a duplicate question in the DB
     *
     * @param string $questionText
     * @param string[] $answerTexts
     * @param string $feedback
     * @return Entity\Question|null
     */
    public function findDuplicationQuestion($questionText, $answerTexts, $feedback)
    {
        $dql = "
            SELECT q, a
            FROM Hondros\Api\Model\Entity\Question q
            JOIN q.answers a
            WHERE q.questionText = :questionText
        ";

        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameter('questionText', $questionText);
        $questions = $query->getResult();

        /** @var Entity\Question[] $question */
        foreach ($questions as $question) {
            if (!empty($feedback) && $question->getFeedback() != $feedback) {
                continue;
            }

            foreach ($question->getAnswers() as $answer) {
                if (!in_array($answer->getAnswerText(), $answerTexts)) {
                    continue;
                }
            }

            return $question;
        }

        return null;
    }
}
