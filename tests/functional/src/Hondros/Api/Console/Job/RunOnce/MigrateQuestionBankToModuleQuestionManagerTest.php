<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 4/14/17
 * Time: 6:10 PM
 */

namespace Hondros\Functional\Api\Console\Job\RunOnce;

use Hondros\Api\Console\Job\RunOnce\MigrateQuestionBankToModuleQuestionManager;
use Hondros\Api\Model\Entity;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Hondros\Test\FunctionalAbstract;
use Exception;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateQuestionBankToModuleQuestionManagerTest extends FunctionalAbstract
{
    use EntityGeneratorUtil;

    /**
     * @var MigrateQuestionBankToModuleQuestionManager
     */
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new MigrateQuestionBankToModuleQuestionManager($this->getServiceManager());
    }

    protected function tearDown(): void
    {
        $this->manager = null;

        parent::tearDown();
    }

    public function testMigrateQuestionsNoModules()
    {
        $this->expectException(Exception::class);
        $response = $this->manager->migrateQuestions();
    }

    /**
     * when this runs, we should have 4 types of module_questions found, each with many questions. we need to make
     * sure we only add module questions for active questions.
     */
    public function testMigrateQuestionsValid()
    {
        $this->assertTrue($this->createModuleData());

        // add inactive question and make sure the module question doesn't get set
        $questionBank = $this->getServiceManager()->get('questionBankRepository')
            ->findOneByType(Entity\QuestionBank::TYPE_STUDY, ['id' => 'desc']);
        $questions = $this->getServiceManager()->get('questionRepository')
            ->findAll();
        $totalQuestions = count($questions);

        $question = $this->generateQuestionWithAnswers();
        $question->setActive(false);
        $question->setQuestionBank($questionBank);
        $this->getEntityManager()->persist($question->getAnswers()[0]);
        $this->getEntityManager()->persist($question);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        // wipe out module question first
        $this->getEntityManager()->createQuery("DELETE Hondros\Api\Model\Entity\ModuleQuestion")->execute();

        $response = $this->manager->migrateQuestions();

        $this->getEntityManager()->clear();

        $questions = $this->getServiceManager()->get('questionRepository')
            ->findAll();
        $this->assertCount($totalQuestions + 1, $questions);

        // should be 37 from excel import, 1 above but it's inactive so it shouldn't create a module question
        $this->assertEquals(37, $response['migrated']);

        /** @var Entity\ModuleQuestion[] $moduleQuestions */
        $moduleQuestions = $this->getServiceManager()->get('moduleQuestionRepository')->findAll();
        $this->assertNotEmpty($moduleQuestions);
        $this->assertCount(37, $moduleQuestions);

        $typeCount = [
            'study' => [],
            'practice' => [],
            'exam' => [],
            'preassessment' => []
        ];

        foreach ($moduleQuestions as $moduleQuestion) {
            $typeCount[$moduleQuestion->getType()][] = $moduleQuestion->getQuestionId();
        }

        // study should be 8 - 8 from excel and the above added is inactive so shouldn't be there
        $this->assertCount(8, $typeCount['study']);
        $this->assertCount(9, $typeCount['practice']);
        $this->assertCount(10, $typeCount['exam']);
        $this->assertCount(10, $typeCount['preassessment']);

        $this->assertEquals($typeCount['exam'], $typeCount['preassessment']);
    }

    /**
     * an ugly hack was implemented early on to "hide" questions by moving them to question bank 0 instead of
     * setting the question to inactive.
     */
    public function testDisableHiddenQuestions()
    {
        $questionBank = new Entity\QuestionBank();
        $questionBank->setId(0)
            ->getType('multi');

        $question = $this->generateQuestionWithAnswers(Entity\Question::TYPE_MULTI);
        $question->setQuestionBank($questionBank);

        $this->getEntityManager()->persist($question->getAnswers()[0]);
        $this->getEntityManager()->persist($question->getAnswers()[1]);
        $this->getEntityManager()->persist($question->getAnswers()[2]);
        $this->getEntityManager()->persist($question->getAnswers()[3]);
        $this->getEntityManager()->persist($question);
        $this->getEntityManager()->persist($questionBank);

        $this->getEntityManager()->flush();
        $questionId = $question->getId();
        $questionBankId = $questionBank->getId();
        $this->getEntityManager()->clear();

        // change the question bank id to look for this last one
        $reflectionClass = new ReflectionClass($this->manager);
        $property = $reflectionClass->getProperty('hiddenQuestionBankId');
        $property->setAccessible(true);
        $property->setValue($this->manager, $questionBankId);

        $response = $this->manager->disableHiddenQuestions();
        $this->getEntityManager()->clear();

        $moduleQuestion = $this->getServiceManager()->get('moduleQuestionRepository')->findOneByQuestionId($questionId);
        $this->assertEmpty($moduleQuestion);

        /** @var Entity\Question $question */
        $question = $this->getServiceManager()->get('questionRepository')->find($questionId);
        $this->assertFalse($question->getActive());
        $this->assertNotNull($question->getModified());
        $this->assertGreaterThan(0, $response['disabled']);
    }

    /**
     * before we remove dups within all content, we need to remove dups inside. 3 questions, one should be removed.
     */
    public function testRemoveDuplicateQuestionWithinQuestionBank()
    {
        $this->createQuestionsForRemoveDuplicateDataInQuestionBank();

        $enrollments = $this->getServiceManager()->get('enrollmentRepository')->findBy([], ['id' => 'desc'], 1);
        $this->assertCount(1, $enrollments);
        /** @var Entity\Enrollment $enrollment */
        $enrollment = $enrollments[0];

        $modules = $this->getServiceManager()->get('moduleRepository')->findBy([], ['id' => 'desc'], 1);
        $this->assertCount(1, $modules);
        /** @var Entity\Module $module */
        $module = $modules[0];

        /** @var Entity\Question[] $questions */
        $questions = $this->getServiceManager()->get('questionRepository')
            ->findByQuestionBank($module->getStudyBankId());
        $this->assertCount(3, $questions);

        /** @var Entity\Progress $progress */
        $progress = $this->getServiceManager()->get('progressRepository')->findOneByEnrollment($enrollment->getId());
        $progressQuestions = $this->getServiceManager()->get('progressQuestionRepository')->findBy([
            'progress' => $progress->getId()
        ]);
        $this->assertCount(3, $progressQuestions);

        /** @var Entity\AssessmentAttempt $assessmentAttempt */
        $assessmentAttempt = $this->getServiceManager()->get('assessmentAttemptRepository')
            ->findOneByEnrollment($enrollment->getId());
        $assessmentAttemptQuestions = $this->getServiceManager()->get('assessmentAttemptQuestionRepository')->findBy([
            'assessmentAttempt' => $assessmentAttempt->getId()
        ]);
        $this->assertCount(3, $assessmentAttemptQuestions);

        /** @var Entity\ModuleAttempt $moduleAttempt */
        $moduleAttempt = $this->getServiceManager()->get('moduleAttemptRepository')
            ->findOneByEnrollment($enrollment->getId());
        $moduleAttemptQuestions = $this->getServiceManager()->get('moduleAttemptQuestionRepository')->findBy([
            'moduleAttempt' => $moduleAttempt->getId()
        ]);
        $this->assertCount(3, $moduleAttemptQuestions);

        $output = \Mockery::mock('Output', 'Symfony\Component\Console\Output\ConsoleOutput');
        $output->shouldReceive('writeln')->andReturnNull();
        $response = $this->manager->removeDuplicateQuestions($output);

        $this->assertEquals(1, $response['deletedWithinCount']);

        /** @var Entity\Question[] $questions */
        $questions = $this->getServiceManager()->get('questionRepository')
            ->findByQuestionBank($module->getStudyBankId());
        $this->assertCount(2, $questions);

        $progress = $this->getServiceManager()->get('progressRepository')->findOneByEnrollment($enrollment->getId());
        $progressQuestions = $this->getServiceManager()->get('progressQuestionRepository')->findBy([
            'progress' => $progress->getId()
        ]);
        $this->assertCount(2, $progressQuestions);

        $assessmentAttempt = $this->getServiceManager()->get('assessmentAttemptRepository')
            ->findOneByEnrollment($enrollment->getId());
        $assessmentAttemptQuestions = $this->getServiceManager()->get('assessmentAttemptQuestionRepository')->findBy([
            'assessmentAttempt' => $assessmentAttempt->getId()
        ]);
        $this->assertCount(2, $assessmentAttemptQuestions);

        $moduleAttempt = $this->getServiceManager()->get('moduleAttemptRepository')
            ->findOneByEnrollment($enrollment->getId());
        $moduleAttemptQuestions = $this->getServiceManager()->get('moduleAttemptQuestionRepository')->findBy([
            'moduleAttempt' => $moduleAttempt->getId()
        ]);
        $this->assertCount(2, $moduleAttemptQuestions);

    }

    public function testRemoveDuplicateQuestions()
    {
        $this->createQuestionsForRemoveDuplicateData();

        $questions = $this->getServiceManager()->get('questionRepository')->findAll();
        $this->assertNotEmpty($questions);
        $totalQuestions = count($questions);

        $hashes = [];
        /** @var Entity\Question $question */
        foreach ($questions as $question) {
            $questionTexts = $question->getQuestionText() . $question->getFeedback();
            $answerTexts = null;

            $answers = $question->getAnswers()->getIterator();
            array_walk($answers, function (Entity\Answer $answer) use (&$answerTexts) {
                $answerTexts .= $answer->getAnswerText();
            });

            $answerHash = md5($answerTexts);
            $hash = md5($questionTexts . $answerHash);

            if (empty($hashes[$hash])) {
                $hashes[$hash] = [$question->getId()];
            } else {
                $hashes[$hash][] = $question->getId();
            }
        }

        $matchingHashes = [];
        foreach ($hashes as $hash => $ids) {
            if (count($ids) > 1) {
                $matchingHashes[] = $ids;
            }
        }

        $this->assertGreaterThan(0, $matchingHashes);

        /** @var Entity\ModuleQuestion[] $moduleQuestions */
        $moduleQuestions = $this->getServiceManager()->get('moduleQuestionRepository')->findAll();
        $totalModuleQuestions = count($moduleQuestions);
        $this->assertGreaterThan(0, $totalModuleQuestions);

        /** @var Entity\ProgressQuestion[] $progressQuestions */
        $progressQuestions = $this->getServiceManager()->get('progressQuestionRepository')->findAll();
        $totalProgressQuestions = count($progressQuestions);
        $this->assertGreaterThan(0, $totalProgressQuestions);
        $progressQuestionQuestionId = $progressQuestions[$totalProgressQuestions - 1]->getQuestionId();

        /** @var Entity\AssessmentAttemptQuestion[] $assessmentAttemptQuestions */
        $assessmentAttemptQuestions = $this->getServiceManager()->get('assessmentAttemptQuestionRepository')->findAll();
        $totalAssessmentAttemptQuestions = count($assessmentAttemptQuestions);
        $this->assertGreaterThan(0, $totalAssessmentAttemptQuestions);
        $assessmentAttemptQuestionId = $assessmentAttemptQuestions[$totalAssessmentAttemptQuestions - 1]->getQuestionId();

        /** @var Entity\ModuleAttemptQuestion[] $moduleAttemptQuestions */
        $moduleAttemptQuestions = $this->getServiceManager()->get('moduleAttemptQuestionRepository')->findAll();
        $totalModuleAttemptQuestions = count($moduleAttemptQuestions);
        $this->assertGreaterThan(0, $totalModuleAttemptQuestions);
        $moduleAttemptQuestionId = $moduleAttemptQuestions[$totalModuleAttemptQuestions - 1]->getQuestionId();

        // do the thing
        $output = \Mockery::mock('Output', 'Symfony\Component\Console\Output\ConsoleOutput');
        $output->shouldReceive('writeln')->andReturnNull();
        $response = $this->manager->removeDuplicateQuestions($output);

        // clean up to avoid weird results
        $this->getEntityManager()->clear();

        $ids = $response['matching'];

        $this->assertNotEmpty($ids);
        $this->assertCount(count($matchingHashes), $ids);

        $question1Ids = $ids[0];

        $this->assertCount(2, $question1Ids);

        $questions = $this->getServiceManager()->get('questionRepository')->findAll();
        $afterTotalQuestions = count($questions);
        $this->assertEquals($totalQuestions - count($matchingHashes), $afterTotalQuestions);

        $question1IdFound = false;
        foreach ($questions as $question) {
            $this->assertNotEquals($question1Ids[1], $question->getId());

            if ($question->getId() == $question1Ids[0]) {
                $question1IdFound = true;
            }
        }
        $this->assertTrue($question1IdFound);

        // make sure the other assets where updated
        $moduleQuestions = $this->getServiceManager()->get('moduleQuestionRepository')->findAll();
        $this->assertCount($totalModuleQuestions, $moduleQuestions);

        // the last two should be the new ones added for this test
        $moduleQuestions = array_slice($moduleQuestions, -2);
        foreach ($moduleQuestions as $moduleQuestion) {
            $this->assertEquals(key($response['deleted']), $moduleQuestion->getQuestionId());
        }

        /** @var Entity\ProgressQuestion[] $progressQuestions */
        $progressQuestions = $this->getServiceManager()->get('progressQuestionRepository')->findAll();
        $this->assertCount($totalProgressQuestions, $progressQuestions);
        $this->assertNotEquals($progressQuestionQuestionId , $progressQuestions[$totalProgressQuestions - 1]->getQuestionId());
        $this->assertEquals(key($response['deleted']), $progressQuestions[$totalProgressQuestions - 1]->getQuestionId());

        /** @var Entity\AssessmentAttemptQuestion[] $assessmentAttemptQuestions */
        $assessmentAttemptQuestions = $this->getServiceManager()->get('assessmentAttemptQuestionRepository')->findAll();
        $this->assertCount($totalAssessmentAttemptQuestions, $assessmentAttemptQuestions);
        $this->assertEquals($assessmentAttemptQuestionId, $assessmentAttemptQuestions[$totalAssessmentAttemptQuestions - 1]->getQuestionId());

        /** @var Entity\ModuleAttemptQuestion[] $moduleAttemptQuestions */
        $moduleAttemptQuestions = $this->getServiceManager()->get('moduleAttemptQuestionRepository')->findAll();
        $this->assertCount($totalModuleAttemptQuestions, $moduleAttemptQuestions);
        $this->assertNotEquals($moduleAttemptQuestionId , $moduleAttemptQuestions[$totalModuleAttemptQuestions - 1]->getQuestionId());
        $this->assertEquals(key($response['deleted']), $moduleAttemptQuestions[$totalModuleAttemptQuestions - 1]->getQuestionId());

        // match counts in response
        $this->assertEquals(3, $response['replacedCount']);
        $this->assertEquals(1, $response['deletedCount']);
    }

    /**
     * make sure running this twice doesn't produce unexpected results
     */
    public function testRemoveDuplicateQuestionsTwice()
    {
        $this->createQuestionsForRemoveDuplicateData();
        $output = \Mockery::mock('Output', 'Symfony\Component\Console\Output\ConsoleOutput');
        $output->shouldReceive('writeln')->andReturnNull();
        $response1 = $this->manager->removeDuplicateQuestions($output);

        // clean up to avoid weird results
        $this->getEntityManager()->clear();

        $this->assertNotEquals(0, count($response1['matching']));
        $this->assertNotEquals(0, count($response1['deleted']));
        $this->assertNotEquals(0, $response1['replacedCount']);
        $this->assertNotEquals(0, $response1['deletedCount']);

        $response2 = $this->manager->removeDuplicateQuestions($output);

        // clean up to avoid weird results
        $this->getEntityManager()->clear();

        $this->assertEquals(0, count($response2['matching']));
        $this->assertEquals(0, count($response2['deleted']));
        $this->assertEquals(0, $response2['replacedCount']);
        $this->assertEquals(0, $response2['deletedCount']);
    }

    /**
     * add some questions
     *
     * question 3 should replace 4 as they are the only real matches
     */
    protected function createQuestionsForRemoveDuplicateData()
    {
        $questionBank1 = $this->generateQuestionBank();
        $question1 = $this->generateQuestionWithAnswers();
        $question1->setQuestionBank($questionBank1);

        $questionBank2 = $this->generateQuestionBank();
        $question2 = $this->generateQuestionWithAnswers();
        $question2->setQuestionText($question1->getQuestionText())
            ->setFeedback($question1->getFeedback())
            ->setQuestionBank($questionBank2);
        $question2->getAnswers()[0]->setAnswerText('different');
        $question3 = $this->generateQuestionWithAnswers();
        $question3->setQuestionBank($questionBank2);

        $questionBank3 = $this->generateQuestionBank();
        $question4 = $this->generateQuestionWithAnswers();
        $question4->setQuestionText($question3->getQuestionText())
            ->setFeedback($question3->getFeedback())
            ->setQuestionBank($questionBank3);
        $answer = $question4->getAnswers()[0];
        $answer->setAnswerText($question3->getAnswers()[0]->getAnswerText());

        $this->getEntityManager()->persist($questionBank1);
        $this->getEntityManager()->persist($question1->getAnswers()[0]);
        $this->getEntityManager()->persist($question1);
        $this->getEntityManager()->persist($questionBank2);
        $this->getEntityManager()->persist($question2->getAnswers()[0]);
        $this->getEntityManager()->persist($question2);
        $this->getEntityManager()->persist($question3->getAnswers()[0]);
        $this->getEntityManager()->persist($question3);

        $this->getEntityManager()->persist($questionBank3);
        $this->getEntityManager()->persist($question4->getAnswers()[0]);
        $this->getEntityManager()->persist($question4);

        // need some other assets
        $industry = $this->generateIndustry();
        $module1 = $this->generateModule()
            ->setIndustry($industry);
        $moduleQuestion1 = $this->generateModuleQuestion()
            ->setModule($module1)
            ->setQuestion($question3);

        $module2 = $this->generateModule()
            ->setIndustry($industry);
        $moduleQuestion2 = $this->generateModuleQuestion()
            ->setModule($module2)
            ->setQuestion($question4);

        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->persist($module1);
        $this->getEntityManager()->persist($module2);
        $this->getEntityManager()->persist($moduleQuestion1);
        $this->getEntityManager()->persist($moduleQuestion2);

        // need to test progress_question also so need enrollment
        $organization = $this->generateOrganization();
        $exam = $this->generateExam()
            ->setIndustry($industry);
        $user = $this->generateUser();
        $enrollment = $this->generateEnrollment()
            ->setUser($user)
            ->setExam($exam)
            ->setOrganization($organization);
        $progress = $this->generateProgress()
            ->setModule($module2)
            ->setEnrollment($enrollment);
        $progressQuestion = (new Entity\ProgressQuestion())
            ->setProgress($progress)
            ->setQuestion($question4);

        $this->getEntityManager()->persist($organization);
        $this->getEntityManager()->persist($exam);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->persist($enrollment);
        $this->getEntityManager()->persist($progress);
        $this->getEntityManager()->persist($progressQuestion);

        // next need assessment question, make it so that doesn't get updated
        $assessmentAttempt = $this->generateAssessmentAttempt()
            ->setEnrollment($enrollment);
        $assessmentAttemptQuestion = (new Entity\AssessmentAttemptQuestion())
            ->setAssessmentAttempt($assessmentAttempt)
            ->setModule($module1)
            ->setQuestion($question1)
            ->setSort(0);

        $this->getEntityManager()->persist($assessmentAttempt);
        $this->getEntityManager()->persist($assessmentAttemptQuestion);

        // finally module attempt question and make that update
        $moduleAttempt = $this->generateModuleAttempt()
            ->setEnrollment($enrollment)
            ->setModule($module1);
        $moduleAttemptQuestion = (new Entity\ModuleAttemptQuestion())
            ->setModuleAttempt($moduleAttempt)
            ->setQuestion($question4)
            ->setSort(0);

        $this->getEntityManager()->persist($moduleAttempt);
        $this->getEntityManager()->persist($moduleAttemptQuestion);

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }

    /**
     * make sure the progress rows are removed when removing a dup within a question bank
     */
    protected function createQuestionsForRemoveDuplicateDataInQuestionBank()
    {
        $questionBank1 = $this->generateQuestionBank();

        $question1 = $this->generateQuestionWithAnswers();
        $question1->setQuestionBank($questionBank1);

        // dup of question 1
        $question2 = $this->generateQuestionWithAnswers();
        $question2->setQuestionText($question1->getQuestionText())
            ->setFeedback($question1->getFeedback())
            ->setQuestionBank($questionBank1);
        $question2->getAnswers()[0]->setAnswerText($question1->getAnswers()[0]->getAnswerText());

        $question3 = $this->generateQuestionWithAnswers();
        $question3->setQuestionBank($questionBank1);

        $this->getEntityManager()->persist($questionBank1);
        $this->getEntityManager()->persist($question1->getAnswers()[0]);
        $this->getEntityManager()->persist($question1);
        $this->getEntityManager()->persist($question2->getAnswers()[0]);
        $this->getEntityManager()->persist($question2);
        $this->getEntityManager()->persist($question3->getAnswers()[0]);
        $this->getEntityManager()->persist($question3);

        // need some other assets
        $industry = $this->generateIndustry();
        $module1 = $this->generateModule()
            ->setIndustry($industry)
            ->setStudyBank($questionBank1);

        $this->getEntityManager()->persist($industry);
        $this->getEntityManager()->persist($module1);

        // need to test progress_question also so need enrollment
        $organization = $this->generateOrganization();
        $exam = $this->generateExam()
            ->setIndustry($industry);
        $user = $this->generateUser();
        $enrollment = $this->generateEnrollment()
            ->setUser($user)
            ->setExam($exam)
            ->setOrganization($organization);
        $progress = $this->generateProgress()
            ->setModule($module1)
            ->setEnrollment($enrollment);
        $progressQuestion1 = (new Entity\ProgressQuestion())
            ->setProgress($progress)
            ->setQuestion($question1);
        $progressQuestion2 = (new Entity\ProgressQuestion())
            ->setProgress($progress)
            ->setQuestion($question2);
        $progressQuestion3 = (new Entity\ProgressQuestion())
            ->setProgress($progress)
            ->setQuestion($question3);

        $this->getEntityManager()->persist($organization);
        $this->getEntityManager()->persist($exam);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->persist($enrollment);
        $this->getEntityManager()->persist($progress);
        $this->getEntityManager()->persist($progressQuestion1);
        $this->getEntityManager()->persist($progressQuestion2);
        $this->getEntityManager()->persist($progressQuestion3);

        $assessmentAttempt = $this->generateAssessmentAttempt()
            ->setEnrollment($enrollment);
        $assessmentAttemptQuestion1 = (new Entity\AssessmentAttemptQuestion())
            ->setAssessmentAttempt($assessmentAttempt)
            ->setModule($module1)
            ->setQuestion($question1)
            ->setSort(0);
        $assessmentAttemptQuestion2 = (new Entity\AssessmentAttemptQuestion())
            ->setAssessmentAttempt($assessmentAttempt)
            ->setModule($module1)
            ->setQuestion($question2)
            ->setSort(1);
        $assessmentAttemptQuestion3 = (new Entity\AssessmentAttemptQuestion())
            ->setAssessmentAttempt($assessmentAttempt)
            ->setModule($module1)
            ->setQuestion($question3)
            ->setSort(2);

        $this->getEntityManager()->persist($assessmentAttempt);
        $this->getEntityManager()->persist($assessmentAttemptQuestion1);
        $this->getEntityManager()->persist($assessmentAttemptQuestion2);
        $this->getEntityManager()->persist($assessmentAttemptQuestion3);

        $moduleAttempt = $this->generateModuleAttempt()
            ->setEnrollment($enrollment)
            ->setModule($module1);
        $moduleAttemptQuestion1 = (new Entity\ModuleAttemptQuestion())
            ->setModuleAttempt($moduleAttempt)
            ->setQuestion($question1)
            ->setSort(0);
        $moduleAttemptQuestion2 = (new Entity\ModuleAttemptQuestion())
            ->setModuleAttempt($moduleAttempt)
            ->setQuestion($question2)
            ->setSort(0);
        $moduleAttemptQuestion3 = (new Entity\ModuleAttemptQuestion())
            ->setModuleAttempt($moduleAttempt)
            ->setQuestion($question3)
            ->setSort(0);

        $this->getEntityManager()->persist($moduleAttempt);
        $this->getEntityManager()->persist($moduleAttemptQuestion1);
        $this->getEntityManager()->persist($moduleAttemptQuestion2);
        $this->getEntityManager()->persist($moduleAttemptQuestion3);

        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }

    /**
     * create some data from the valid module file
     */
    protected function createModuleData()
    {
        $industry = $this->getServiceManager()->get('industryRepository')->findOneByName('Securities');
        if (empty($industry)) {
            $industry = new Entity\Industry();
            $industry->setName('Securities');
            $this->getEntityManager()->persist($industry);
            $this->getEntityManager()->flush();
        }

        $moduleFile = getcwd() . DIRECTORY_SEPARATOR . 'tests/unit/assets/import/Module_valid1.xlsx';
        $response = $this->getServiceManager()->get('contentImporter')->importModule($moduleFile);
        $this->getEntityManager()->clear();

        return $response;
    }
}
