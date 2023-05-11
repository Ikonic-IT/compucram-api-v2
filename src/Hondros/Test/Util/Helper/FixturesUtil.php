<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 4/24/17
 * Time: 4:11 PM
 */

namespace Hondros\Test\Util\Helper;

use Doctrine\ORM\EntityManager;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Hondros\Api\Model\Entity;

/**
 * Class FixturesUtil
 * @package Hondros\Test\Util\Helper
 * @todo wait until we move to the new module question schema to start using this util. Not ready to be used yet
 */
trait FixturesUtil
{
    use EntityGeneratorUtil;

    /**
     * @param EntityManager $entityManager
     * @param Entity\Exam $exam
     * @return Entity\Enrollment
     */
    function createEnrollment($entityManager, $exam = null)
    {
        $organization = $this->createOrganization($entityManager);

        if (is_null($exam)) {
            $exam = $this->createExam($entityManager);
        }

        $user = $this->createUser($entityManager);
        $enrollment = $this->generateEnrollment()
            ->setUser($user)
            ->setUserId($user->getId())
            ->setExam($exam)
            ->setExamId($exam->getId())
            ->setOrganization($organization)
            ->setOrganizationId($organization->getId())
            ->setStatus(Entity\Enrollment::STATUS_ACTIVE)
            ->setShowPreAssessment(true)
            ->setCreated(new \DateTime());

        $entityManager->persist($enrollment);

        $entityManager->flush();

        return $enrollment;
    }

    /**
     * @param EntityManager $entityManager
     * @return Entity\Industry
     */
    function createIndustry($entityManager)
    {
        $obj = $this->generateIndustry();

        $entityManager->persist($obj);
        $entityManager->flush();

        return $obj;
    }

    /**
     * @param EntityManager $entityManager
     * @return Entity\Organization
     */
    function createOrganization($entityManager)
    {
        $obj = $this->generateOrganization();

        $entityManager->persist($obj);
        $entityManager->flush();

        return $obj;
    }

    /**
     * @param EntityManager $entityManager
     * @return Entity\User
     */
    function createUser($entityManager)
    {
        $obj = $this->generateUser();

        $entityManager->persist($obj);
        $entityManager->flush();

        return $obj;
    }

    /**
     * @param EntityManager $entityManager
     * @param int $moduleCount
     * @return Entity\Exam
     */
    function createExam($entityManager, $moduleCount = 3)
    {
        $industry = $this->createIndustry($entityManager);
        $exam = $this->generateExam();
        $exam->setIndustry($industry);
        $entityManager->persist($exam);

        $modules = [];
        for ($x = 0; $x < $moduleCount; $x++) {
            $modules[] = $this->createModuleWithQuestions($entityManager, $industry);
        }

        $sort = 0;
        foreach ($modules as $module) {
            /** @var Entity\ExamModule $examModule */
            $examModule = $this->generateExamModule();
            $examModule->setSort($sort)
                ->setExam($exam)
                ->setModule($module)
                ->setPracticeQuestions(2)
                ->setExamQuestions(1)
                ->setPreassessmentQuestions(1);
            $sort++;
            $entityManager->persist($examModule);
        }

        $entityManager->flush();

        return $exam;
    }

    /**
     * @param EntityManager $entityManager
     * @param Entity\Industry $industry
     * @return Entity\Module
     */
    function createModuleWithQuestions($entityManager, $industry = null)
    {
        $module = $this->generateModule();

        if (is_null($industry)) {
            $industry = $this->createIndustry($entityManager);
        }

        $module->setIndustry($industry);
        $entityManager->persist($module);

        $types = [
            'study',
            'practice',
            'exam',
            'preassessment'
        ];

        foreach ($types as $type) {
            $questionType = $type == 'study' ? Entity\Question::TYPE_VOCAB : Entity\Question::TYPE_MULTI;
            $questions = $this->createQuestions($entityManager, $questionType, rand(2, 10));

            /** @var Entity\Question $question */
            // adding setQuestionId and setModuleId breaks doctrine persist
            foreach ($questions as $question) {
                $moduleQuestion = (new Entity\ModuleQuestion())
                    ->setModule($module)
                    ->setQuestion($question)
                    ->setType($type);

                $entityManager->persist($moduleQuestion);
            }
        }

        $entityManager->flush();

        return $module;
    }

    /**
     * @param EntityManager $entityManager
     * @param string $type
     * @param int $count
     * @return array
     */
    function createQuestions($entityManager, $type, $count)
    {
        $questions = [];

        for ($x = 0; $x < $count; $x++) {
            $questions[$x] = $this->generateQuestionWithAnswers($type);
            foreach ($questions[$x]->getAnswers() as $answer) {
                $entityManager->persist($answer);
            }
            $entityManager->persist($questions[$x]);
        }

        $entityManager->flush();

        return $questions;
    }
}