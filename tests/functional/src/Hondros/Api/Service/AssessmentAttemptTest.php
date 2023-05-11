<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/29/17
 * Time: 6:24 PM
 */

namespace Hondros\Functional\Api\Service;

use Hondros\Api\Model\Entity;
use Hondros\Api\Service\AssessmentAttempt;
use Hondros\Api\Service\AssessmentAttemptQuestion;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Hondros\Test\FunctionalAbstract;
use Exception;
use Hondros\Test\Util\Helper\FixturesUtil;

class AssessmentAttemptTest extends FunctionalAbstract
{
    use FixturesUtil {
    }

    /**
     * @var \Hondros\Api\Service\AssessmentAttempt
     */
    protected $assessmentAttemptService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assessmentAttemptService = $this->getServiceManager()->get('assessmentAttemptService');
    }

    protected function tearDown(): void
    {
        $this->assessmentAttemptService = null;

        parent::tearDown();
    }

    /**
     * make sure we have the right number of questions for the right modules and no dups
     */
    public function testCreateAttemptRandomOrder()
    {
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $params = ['type' => Entity\AssessmentAttempt::TYPE_EXAM];

        $assessmentAttempt = $this->assessmentAttemptService->createAttempt($enrollment->getId(), $params);
        $assessmentAttemptQuestions = $this->getServiceManager()->get('assessmentAttemptQuestionRepository')
            ->findByAssessmentAttemptId($assessmentAttempt['id']);

        $this->assertCount($assessmentAttempt['questionCount'], $assessmentAttemptQuestions);

        $examModules = $this->getServiceManager()->get('examModuleRepository')->findBy(['exam' => $enrollment->getExam()->getId()]);
        $examModuleQuestions = [];
        /** @var Entity\ExamModule $examModule */
        foreach ($examModules as $examModule) {
            $examModuleQuestions[$examModule->getModule()->getId()] = $examModule->getExamQuestions();
        }

        $this->assertEquals($assessmentAttempt['questionCount'], array_sum(array_values($examModuleQuestions)));

        $index = 1;
        $foundId = [];
        $questionsFound = [];
        foreach ($assessmentAttemptQuestions as $assessmentAttemptQuestion) {
            $this->assertEquals($index++, $assessmentAttemptQuestion->getSort());

            $this->assertNotContains($assessmentAttemptQuestion->getQuestion()->getId(), $foundId);
            $foundId[] = $assessmentAttemptQuestion->getQuestion()->getId();

            if (empty($questionsFound[$assessmentAttemptQuestion->getModule()->getId()])) {
                $questionsFound[$assessmentAttemptQuestion->getModule()->getId()] = 0;
            }
            $questionsFound[$assessmentAttemptQuestion->getModule()->getId()]++;
        }

        foreach ($examModuleQuestions as $key => $value) {
            $this->assertEquals($examModuleQuestions[$key], $questionsFound[$key]);
        }
    }

    /**
     * Pre-assessment attempts should have the same questions
     * An attempt should not have any questions currently marked as inactive
     */
    public function testCreateAttemptPreAssessmentNoInactiveQuestions()
    {
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $params = ['type' => Entity\AssessmentAttempt::TYPE_PREASSESSMENT];

        $assessmentAttempt = $this->assessmentAttemptService->createAttempt($enrollment->getId(), $params);

        /** @var AssessmentAttemptQuestion[] $assessmentAttemptQuestions */
        $assessmentAttemptQuestions = $this->getServiceManager()->get('assessmentAttemptQuestionRepository')
            ->findByAssessmentAttemptId($assessmentAttempt['id']);

        $this->assertCount($assessmentAttempt['questionCount'], $assessmentAttemptQuestions);

        $questionIds = [];
        foreach ($assessmentAttemptQuestions as $assessmentAttemptQuestion) {
            $questionIds[] = $assessmentAttemptQuestion->getQuestionId();
        }

        $assessmentAttempt = $this->assessmentAttemptService->createAttempt($enrollment->getId(), $params);

        /** @var AssessmentAttemptQuestion[] $assessmentAttemptQuestions */
        $assessmentAttemptQuestions = $this->getServiceManager()->get('assessmentAttemptQuestionRepository')
            ->findByAssessmentAttemptId($assessmentAttempt['id']);

        $this->assertCount($assessmentAttempt['questionCount'], $assessmentAttemptQuestions);

        $newQuestionIds = [];
        foreach ($assessmentAttemptQuestions as $assessmentAttemptQuestion) {
            $newQuestionIds[] = $assessmentAttemptQuestion->getQuestionId();
        }

        $this->assertEquals(sort($questionIds), sort($newQuestionIds));

        // disable one question
        $questionToDisable = $assessmentAttemptQuestions[0]->getQuestion();
        $questionToDisable->setActive(false);
        $this->getEntityManager()->flush($questionToDisable);

        $this->getEntityManager()->clear();

        // this next attempt should have all questions id except for this one
        $assessmentAttempt = $this->assessmentAttemptService->createAttempt($enrollment->getId(), $params);

        /** @var AssessmentAttemptQuestion[] $assessmentAttemptQuestions */
        $assessmentAttemptQuestions = $this->getServiceManager()->get('assessmentAttemptQuestionRepository')
            ->findByAssessmentAttemptId($assessmentAttempt['id']);

        $this->assertCount($assessmentAttempt['questionCount'], $assessmentAttemptQuestions);

        $lastQuestionIds = [];
        foreach ($assessmentAttemptQuestions as $assessmentAttemptQuestion) {
            $lastQuestionIds[] = $assessmentAttemptQuestion->getQuestionId();
        }

        $diffIds = array_diff($newQuestionIds, $lastQuestionIds);
        $this->assertCount(1, $diffIds);

        $diffId = array_values($diffIds)[0];
        $this->assertEquals($questionToDisable->getId(), $diffId);
    }
}
