<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/29/17
 * Time: 6:24 PM
 */

namespace Hondros\Functional\Api\Service;

use Hondros\Api\Model\Entity;
use Hondros\Test\FunctionalAbstract;
use Hondros\Test\Util\Helper\FixturesUtil;

class ModuleAttemptTest extends FunctionalAbstract
{
    use FixturesUtil {
    }

    /**
     * @var \Hondros\Api\Service\ModuleAttempt
     */
    protected $moduleAttemptService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moduleAttemptService = $this->getServiceManager()->get('moduleAttemptService');
    }

    protected function tearDown(): void
    {
        $this->moduleAttemptService = null;

        parent::tearDown();
    }

    /**
     * Make sure inactive questions don't end up in the new attempt. Create an attempt with all
     * questions and count. Then disable a few questions, create a new attempt and make sure
     * it now contains all the questions except for the ones disabled.
     */
    public function testCreateAttemptNoInactiveQuestions()
    {
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $examModules = $this->getServiceManager()->get('examModuleRepository')->findBy(['exam' => $enrollment->getExam()->getId()]);

        // pick the first one
        $examModule = $examModules[0];

        /** @var Entity\Module $module */
        $module = $examModule->getModule();

        $moduleQuestions = $this->getServiceManager()->get('moduleQuestionRepository')->findBy(['module' => $module->getId()]);
        $totalModuleQuestions = count($moduleQuestions);

        $progress = $this->generateProgress(Entity\Progress::TYPE_PRACTICE, $totalModuleQuestions)
            ->setModule($module)
            ->setEnrollment($enrollment);
        $this->getEntityManager()->persist($progress);

        foreach ($moduleQuestions as $moduleQuestion) {
            $question = $moduleQuestion->getQuestion();

            $progressQuestion = (new Entity\ProgressQuestion())
                ->setProgress($progress)
                ->setQuestion($question);

            $this->getEntityManager()->persist($progressQuestion);
        }

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $params = [
            'type' => Entity\ModuleAttempt::TYPE_PRACTICE,
            'quantity' => $totalModuleQuestions
        ];
        $moduleAttempt = $this->moduleAttemptService->createAttempt($enrollment->getId(), $module->getId(), $params);
        $moduleAttemptQuestions = $this->getServiceManager()->get('moduleAttemptQuestionRepository')
            ->findByModuleAttemptId($moduleAttempt['id']);

        $this->assertEquals($totalModuleQuestions, $moduleAttempt['questionCount']);
        $this->assertCount($totalModuleQuestions, $moduleAttemptQuestions);

        // now disable a few questions
        $disabledQuestions = [];
        $moduleQuestions = $this->getServiceManager()->get('moduleQuestionRepository')->findBy(['module' => $module->getId()]);
        for ($x = 0; $x < 5; $x++) {
            $moduleQuestions[$x]->getQuestion()->setActive(false);
            $this->getEntityManager()->persist($moduleQuestions[$x]->getQuestion());
            $disabledQuestions[] = $moduleQuestions[$x]->getQuestion();
        }

        $this->getEntityManager()->flush();

        $moduleAttempt = $this->moduleAttemptService->createAttempt($enrollment->getId(), $module->getId(), $params);
        $moduleAttemptQuestions = $this->getServiceManager()->get('moduleAttemptQuestionRepository')
            ->findByModuleAttemptId($moduleAttempt['id']);

        $this->assertEquals($totalModuleQuestions - 5, $moduleAttempt['questionCount']);
        $this->assertCount($totalModuleQuestions - 5, $moduleAttemptQuestions);

        $disabledQuestionIds = [];
        foreach ($disabledQuestions as $disabledQuestion) {
            $disabledQuestionIds[] = $disabledQuestion->getId();
        }

        $attemptQuestionIds = [];
        foreach ($moduleAttemptQuestions as $moduleAttemptQuestion) {
            $attemptQuestionIds[] = $moduleAttemptQuestion->getQuestion()->getId();
        }

        $this->assertEmpty(array_intersect($disabledQuestionIds, $attemptQuestionIds));
    }

    /**
     * Make sure we have the correct logic for updating the progress score based on the attempt results
     */
    public function testProgressScoreUpdate()
    {
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $modules = $this->getServiceManager()->get('moduleRepository')->findForExam($enrollment->getExam()->getId())
            ->getIterator();
        $module = $modules[0];
        $progress = $this->generateProgress(Entity\Progress::TYPE_PRACTICE)
            ->setEnrollment($enrollment)
            ->setModule($module);

        $this->getEntityManager()->persist($progress);

        $attempt = $this->generateModuleAttempt(Entity\ModuleAttempt::TYPE_PRACTICE)
            ->setEnrollment($enrollment)
            ->setModule($module);

        $this->getEntityManager()->persist($attempt);

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $this->assertEquals(0, $progress->getScore());

        $params = [
            'id' => $attempt->getId(),
            'enrollmentId' => $enrollment->getId(),
            'moduleId' => $module->getId(),
            'type' => Entity\ModuleAttempt::TYPE_PRACTICE,
            'score' => 100
        ];

        $this->moduleAttemptService->update($attempt->getId(), $params);
        $progress = $this->getServiceManager()->get('progressRepository')->findOneById($progress->getId());
        $this->assertEquals(25, $progress->getScore());

        $this->moduleAttemptService->update($attempt->getId(), $params);
        $progress = $this->getServiceManager()->get('progressRepository')->findOneById($progress->getId());
        $this->assertEquals(50, $progress->getScore());

        /** should not change progress score if it's under 60% **/
        $params['score'] = 50;
        $this->moduleAttemptService->update($attempt->getId(), $params);
        $progress = $this->getServiceManager()->get('progressRepository')->findOneById($progress->getId());
        $this->assertEquals(50, $progress->getScore());
    }
}
