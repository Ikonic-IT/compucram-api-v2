<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 5/25/17
 * Time: 11:58 AM
 */

namespace Hondros\Integration\Api\Model\Repository;

use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Test\FunctionalAbstract;
use Hondros\Test\Util\Helper\FixturesUtil;
use Predis\Client as RedisClient;
use Doctrine\DBAL\Logging\DebugStack;

/**
 * Class QuestionTest
 * @package Hondros\Integration\Api\Model\Repository
 *
 * Make sure the question listener is doing what it should keep question data fresh in redis
 */
class QuestionTest extends FunctionalAbstract
{
    use FixturesUtil {}

    /** @var  Repository\Question */
    protected $questionRepository;

    /** @var DebugStack */
    protected $queryStack;

    protected function setUp(): void
    {
        parent::setUp();

        // clear out redis
        $this->getRedis()->flushall();

        $this->questionRepository = $this->getServiceManager()->get('questionRepository');
        $this->queryStack = new DebugStack();
        $this->getEntityManager()->getConfiguration()->setSQLLogger($this->queryStack);
    }

    protected function tearDown(): void
    {
        $this->getEntityManager()->getConfiguration()->setSQLLogger(null);
        unset($this->questionRepository);
        unset($this->queryStack);

        parent::tearDown();
    }

    /**
     * @return RedisClient
     */
    public function getRedis()
    {
        return $this->getServiceManager()->get('redis');
    }

    public function testNewQuestionAddedToRedis()
    {
        /** @var Entity\Question[] $questions */
        $questions = $this->createQuestions($this->getEntityManager(), Entity\Question::TYPE_VOCAB, 1);
        $question = $questions[0];
        $cacheId = Repository\Question::CACHE_ID . $question->getId();

        $questionData = $this->getRedis()->get($cacheId);
        $this->assertNotNull($questionData);

        $questionDecoded = json_decode($questionData);
        $this->assertEquals($question->getId(), $questionDecoded->id);
        $this->assertEquals($question->getType(), $questionDecoded->type);
        $this->assertEquals($question->getQuestionText(), $questionDecoded->questionText);
        $this->assertEquals($question->getFeedback(), $questionDecoded->feedback);
        $this->assertEquals($question->getActive(), $questionDecoded->active);
    }

    public function testExistingQuestionAddedToRedisOnLoad()
    {
        /** @var Entity\Question[] $questions */
        $questions = $this->createQuestions($this->getEntityManager(), Entity\Question::TYPE_VOCAB, 1);
        $question = $questions[0];
        $cacheId = Repository\Question::CACHE_ID . $question->getId();

        $questionData = $this->getRedis()->get($cacheId);
        $this->assertNotNull($questionData);

        // clean up before moving forward to prevent the em from not loading since in mem
        $this->getEntityManager()->detach($question);
        $this->getRedis()->del($cacheId);

        $questionData = $this->getRedis()->get($cacheId);
        $this->assertNull($questionData);

        // load and check again
        $question = $this->questionRepository->find($question->getId());
        $questionData = $this->getRedis()->get($cacheId);
        $this->assertNotNull($questionData);

        $questionDecoded = json_decode($questionData);
        $this->assertEquals($question->getId(), $questionDecoded->id);
        $this->assertEquals($question->getType(), $questionDecoded->type);
        $this->assertEquals($question->getQuestionText(), $questionDecoded->questionText);
        $this->assertEquals($question->getFeedback(), $questionDecoded->feedback);
    }

    public function testFindForModuleTypeMissingAddsToCache()
    {
        $module = $this->createModuleWithQuestions($this->getEntityManager());

        $this->getRedis()->flushAll();
        $this->getEntityManager()->clear();

        $questions = $this->questionRepository->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY);
        $this->assertNotEmpty($questions);

        $keys = $this->getRedis()->keys(Repository\Question::CACHE_ID . '*');
        $this->assertNotEmpty($keys);

        $questionIds = [];
        array_walk($keys, function ($item) use (&$questionIds) {
            $strPos = strrpos($item, ':');
            $questionIds[substr($item, $strPos + 1)] = 1;
        });

        $this->assertGreaterThan(0, count($questions));
        foreach ($questions as $question) {
            $this->assertArrayHasKey($question->getId(), $questionIds);
        }
    }

    public function testFindForModuleTypeWithAnswersTwiceNoDbSecondTime()
    {
        $module = $this->createModuleWithQuestions($this->getEntityManager());

        $this->getRedis()->flushAll();
        $this->getEntityManager()->clear();

        $queryCount = count($this->queryStack->queries);
        $questions = $this->questionRepository->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY);
        $this->assertNotEmpty($questions);

        $newQueryCount = count($this->queryStack->queries);
        $this->assertNotEquals($queryCount, $newQueryCount);

        $questions = $this->questionRepository->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY);
        $this->assertNotEmpty($questions);

        $this->assertCount($newQueryCount, $this->queryStack->queries);
    }

    public function testFindForModuleAllTypesTwiceNoDb()
    {
        $module = $this->createModuleWithQuestions($this->getEntityManager());

        $this->getRedis()->flushAll();
        $this->getEntityManager()->clear();

        $queryCount = count($this->queryStack->queries);
        $studyQuestions = $this->questionRepository->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY);
        $allQuestions = $this->questionRepository->findForModule($module->getId());

        $this->assertNotEmpty($studyQuestions);
        $this->assertNotEmpty($allQuestions);

        $this->assertNotEquals(count($studyQuestions), count($allQuestions));

        $newQueryCount = count($this->queryStack->queries);
        $this->assertNotEquals($queryCount, $newQueryCount);

        $questions = $this->questionRepository->findForModule($module->getId());
        $this->assertNotEmpty($questions);
        $this->assertEquals(count($allQuestions), count($questions));

        $this->assertCount($newQueryCount, $this->queryStack->queries);
    }

    public function testFindByIdsMakeSureCacheWorks()
    {
        $questions = $this->createQuestions($this->getEntityManager(), Entity\Question::TYPE_MULTI, 5);
        $questionIds = [];

        foreach ($questions as $question) {
            $questionIds[] = $question->getId();
        }

        $this->getRedis()->flushAll();
        $this->getEntityManager()->clear();

        /** @var Entity\Question[] $responseQuestions */
        $responseQuestions = $this->questionRepository->findByIdsWithAnswers($questionIds);
        $this->assertCount(count($questions), $responseQuestions);
        $queryCount = count($this->queryStack->queries);

        // make sure we have answers
        foreach ($responseQuestions as $responseQuestion) {
            $this->assertGreaterThan(1, count($responseQuestion->getAnswers()));
        }

        $keys = $this->getRedis()->keys(Repository\Question::CACHE_ID . '*');
        $this->assertNotEmpty($keys);
        $this->assertCount(count($questions), $keys);

        $responseQuestions = $this->questionRepository->findByIdsWithAnswers($questionIds);
        $this->assertCount(count($questions), $responseQuestions);
        $this->assertCount($queryCount, $this->queryStack->queries);

        // make sure we have answers
        foreach ($responseQuestions as $responseQuestion) {
            $this->assertGreaterThan(1, count($responseQuestion->getAnswers()));
        }

        // remove a question and try again
        $this->getEntityManager()->clear();
        $this->getRedis()->del(Repository\Question::CACHE_ID . $responseQuestions[0]->getId());
        $responseQuestions = $this->questionRepository->findByIdsWithAnswers($questionIds);
        $this->assertCount(count($questions), $responseQuestions);
        $newQueryCount = count($this->queryStack->queries);
        $this->assertGreaterThan($queryCount, $newQueryCount);

        // remove an answer and try again
        $this->getEntityManager()->clear();
        $this->getRedis()->del(Repository\Answer::CACHE_ID . $responseQuestions[0]->getAnswers()[0]->getId());
        $responseQuestions = $this->questionRepository->findByIdsWithAnswers($questionIds);
        $this->assertCount(count($questions), $responseQuestions);
        $newerQueryCount = count($this->queryStack->queries);
        $this->assertGreaterThan($newQueryCount, $newerQueryCount);

        // no more queries
        $this->getEntityManager()->clear();
        $responseQuestions = $this->questionRepository->findByIdsWithAnswers($questionIds);
        $this->assertCount(count($questions), $responseQuestions);
        $this->assertCount($newerQueryCount, $this->queryStack->queries);

    }
}
