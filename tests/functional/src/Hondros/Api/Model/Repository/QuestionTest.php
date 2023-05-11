<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 4/24/17
 * Time: 4:01 PM
 */

namespace Hondros\Functional\Api\Model\Repository;

use Hondros\Api\Model\Repository;
use Hondros\Test\Util\Helper\FixturesUtil;
use Hondros\Api\Model\Entity;
use Hondros\Test\FunctionalAbstract;

class QuestionTest extends FunctionalAbstract
{
    use FixturesUtil;

    /**
     * @var Repository\Question
     */
    protected $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->getServiceManager()->get('questionRepository');
    }

    protected function tearDown(): void
    {
        $this->repository = null;

        parent::tearDown();
    }

    public function testFindByIds()
    {
        /** @var Entity\Question[] $questions */
        $questions = $this->createQuestions($this->getEntityManager(), Entity\Question::TYPE_VOCAB, 3);
        $this->assertCount(3, $questions);

        $this->getEntityManager()->clear();

        /** @var Entity\Question[] $questionsResponse */
        $questionsResponse = $this->repository->findByIdsWithAnswers([$questions[0]->getId()]);

        $this->assertCount(1, $questionsResponse);
        $this->assertEquals($questions[0]->getQuestionText(), $questionsResponse[0]->getQuestionText());
        $this->assertEquals($questions[0]->getFeedback(), $questionsResponse[0]->getFeedback());

        $questionsResponse = $this->repository->findByIdsWithAnswers([$questions[1]->getId(),$questions[2]->getId()]);

        $this->assertCount(2, $questionsResponse);
        $this->assertEquals($questions[1]->getQuestionText(), $questionsResponse[0]->getQuestionText());
        $this->assertEquals($questions[1]->getFeedback(), $questionsResponse[0]->getFeedback());
        $this->assertEquals($questions[2]->getQuestionText(), $questionsResponse[1]->getQuestionText());
        $this->assertEquals($questions[2]->getFeedback(), $questionsResponse[1]->getFeedback());
    }

    public function testFindForModuleLimitOffset()
    {
        $module = $this->createModuleWithQuestions($this->getEntityManager());

        $this->getEntityManager()->clear();

        $questions = $this->repository->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY, 1, 0);
        $questionOne = $questions[0];
        $questions = $this->repository->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY, 1, 1);
        $questionTwo = $questions[0];

        $this->assertNotEquals($questionOne->getId(), $questionTwo->getId());

        $questions = $this->repository->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_EXAM, 2, 0);
        $this->assertCount(2, $questions);
        $this->assertNotEquals($questionOne->getId(), $questions[0]->getId());
        $this->assertNotEquals($questionOne->getId(), $questions[1]->getId());
    }

    public function testUpdates()
    {
        $module = $this->createModuleWithQuestions($this->getEntityManager());

        $this->getEntityManager()->clear();

        $questions = $this->repository->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY, 1, 0);
        $questionOne = $questions[0];

        $questionOne->setQuestionText('another');
        $this->getEntityManager()->flush($questionOne);

        $this->getEntityManager()->clear();

        $questions = $this->repository->findForModule($module->getId(), Entity\ModuleQuestion::TYPE_STUDY, 1, 0);
        $questionOneAgain = $questions[0];

        $this->assertEquals($questionOne->getId(), $questionOneAgain->getId());
        $this->assertEquals('another', $questionOneAgain->getQuestionText());
    }
}
