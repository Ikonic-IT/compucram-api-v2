<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/29/17
 * Time: 6:24 PM
 */

namespace Hondros\Functional\Api\Service;

use Hondros\Api\Model\Entity;
use Hondros\Test\Util\Helper\FixturesUtil;
use Mockery as m;
use Hondros\Test\FunctionalAbstract;
use ReflectionClass;
use Exception;
use InvalidArgumentException;

class EnrollmentTest extends FunctionalAbstract
{
    use FixturesUtil {}

    /**
     * @var \Hondros\Api\Service\Enrollment
     */
    protected $enrollmentService;

    /**
     * @var \Mockery\Mock
     */
    protected $mailChimpMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enrollmentService = $this->getServiceManager()->get('enrollmentService');

        $this->mailChimpMock = m::mock('\Hondros\Api\Client\MailChimp');

        // replace mailChimp with mock
        $reflectionClass = new ReflectionClass($this->enrollmentService);
        $property = $reflectionClass->getProperty('mailChimpClient');
        $property->setAccessible(true);
        $property->setValue($this->enrollmentService, $this->mailChimpMock);
    }

    protected function tearDown(): void
    {
        $this->enrollmentService = null;
        $this->mailChimpMock = null;

        m::close();

        parent::tearDown();
    }

    /**
     * @param array $params
     * @param string $errorMessage
     * @dataProvider dataProviderSavePending
     */
    public function testSavePending($params, $errorMessage)
    {
        $afterSubscriber = new \Hondros\Api\Client\MailChimp\Subscriber();

        // for valid scenario do the rest of the setup
        if (empty($errorMessage)) {
            $afterSubscriber->setId(100)
                ->setEmail($params['email'])
                ->setFirstName($params['firstName'])
                ->setLastName($params['lastName'])
                ->setProductId($params['productIds']);

            $this->mailChimpMock->shouldReceive('addSubscriberToList')
                ->with(m::any(), m::type('\Hondros\Api\Client\MailChimp\Subscriber'))
                ->once()
                ->andReturn($afterSubscriber);
        }

        try {
            $response = $this->enrollmentService->savePending($params);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($afterSubscriber->getId(), $response['id']);
    }

    /**
     * @param array $params
     * @param string $errorMessage
     * @dataProvider dataProviderCreate
     */
    public function testCreate($params, $errorMessage)
    {
        /** @var Entity\Organization $organization */
        $organization = null;
        if (!empty($params['organizationId'])) {
            $organization = $this->getServiceManager()->get('organizationRepository')
                ->find($params['organizationId']);
        }

        // make sure free trial calls mailchimp for a valid request
        if (!empty($params['type']) && empty($errorMessage)
            && $params['type'] === Entity\Enrollment::TYPE_TRIAL) {
            $this->mailChimpMock->shouldReceive('addSubscriberToList')
                ->with(m::any(), m::type('\Hondros\Api\Client\MailChimp\Subscriber'))
                ->once();
        }

        // make sure mailchimp moving from pending to enrolled lists works for compucram enrollments
        if (!empty($organization) && $organization->getName() == Entity\Organization::COMPUCRAM
            && strpos($params['userId'], '#found') === false) {

            /** @var Entity\User $user */
            $user = $this->getServiceManager()->get('userRepository')->findOneById($params['userId']);

            $this->mailChimpMock->shouldReceive('isSubscriberInList')
                ->with(m::any(), $user->getEmail())
                ->once()
                ->andReturn(false);
        }

        // test the mailchimp process that removes and adds to list
        if (!empty($organization) && $organization->getName() == Entity\Organization::COMPUCRAM
            && strpos($params['userId'], '#found') !== false && (empty($params['type'])
                || $params['type'] != Entity\Enrollment::TYPE_TRIAL)) {

            $params['userId'] = substr($params['userId'], 0,-6);
            $user = $this->getServiceManager()->get('userRepository')->findOneById($params['userId']);

            $this->mailChimpMock->shouldReceive('isSubscriberInList')
                ->with(m::any(), $user->getEmail())
                ->once()
                ->andReturn(true);

            $this->mailChimpMock->shouldReceive('removeFromList')
                ->with(m::any(), $user->getEmail())
                ->once();

            $this->mailChimpMock->shouldReceive('addSubscriberToList')
                ->with(m::any(), m::type('\Hondros\Api\Client\MailChimp\Subscriber'))
                ->once();
        }

        try {
            $response = $this->enrollmentService->save($params);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        // clean up before we get started
        $this->getEntityManager()->clear();

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertGreaterThan(0, $response['id']);
        $this->assertEquals($params['userId'], $response['userId']);

        if (!empty($params['examId'])) {
            /** @var Entity\Exam $exam */
            $exam = $this->getServiceManager()->get('examRepository')->findOneById($params['examId']);
            $this->assertNotEmpty($exam);
            $this->assertEquals($params['examId'], $response['examId']);
        } else {
            /** @var Entity\Exam $exam */
            $exam = $this->getServiceManager()->get('examRepository')->findOneByCode($params['examCode']);
            $this->assertNotEmpty($exam);
            $this->assertEquals($params['examCode'], $exam->getCode());
        }

        $this->assertEquals($params['organizationId'], $response['organizationId']);
        $this->assertEquals(1, $response['status']);
        $this->assertNull($response['started']);
        $this->assertNotNull($response['created']);

        // make sure default type full
        if (!empty($params['type'])) {
            $this->assertEquals($params['type'], $response['type']);
        } else {
            $this->assertEquals(Entity\Enrollment::TYPE_FULL, $response['type']);
            // should be assigned for full
            $this->assertNotEmpty($response['converted']);
        }

        if ($response['type'] !== Entity\Enrollment::TYPE_FULL) {
            $this->assertNull($response['converted']);
        }

        // perpetual flag shouldn't have expiration
        if (!empty($params['perpetual']) && is_bool($params['perpetual']) && (bool) $params['perpetual']) {
            $this->assertNull($response['expiration']);
        } elseif (is_null($exam->getAccessTime())) {
            $this->assertNull($response['expiration']);
        } else {
            $expectedExpiration = (new \DateTime())->add(new \DateInterval("P{$exam->getAccessTime()}D"));
            $responseExpiration = (new \DateTime())->setTimeStamp($response['expiration']);
            $this->assertEquals($expectedExpiration->format('m-d-Y'), $responseExpiration->format('m-d-Y'));
        }

        if (!empty($params['externalOrderId'])) {
            $this->assertEquals($params['externalOrderId'], $response['externalOrderId']);
        } else {
            $this->assertEmpty($response['externalOrderId']);
        }
    }

    /**
     * make sure enable and disable work
     */
    public function testEnableDisable()
    {
        $objects = $this->createRequiredObjectsForEnrollment();
        $params = [
            'userId' => $objects['user']->getId(),
            'examId' => $objects['exam']->getId(),
            'organizationId' => $objects['organization']->getId(),
        ];
        $response = $this->enrollmentService->save($params);
        $this->assertEquals(Entity\Enrollment::STATUS_ACTIVE, $response['status']);

        $response = $this->enrollmentService->disable($response['id']);
        $this->assertEquals(Entity\Enrollment::STATUS_INACTIVE, $response['status']);

        $response = $this->enrollmentService->enable($response['id']);
        $this->assertEquals(Entity\Enrollment::STATUS_ACTIVE, $response['status']);
    }

    public function testExtendExpirationInFutureDateTwice()
    {
        $objects = $this->createRequiredObjectsForEnrollment();
        $params = [
            'userId' => $objects['user']->getId(),
            'examId' => $objects['exam']->getId(),
            'organizationId' => $objects['organization']->getId()
        ];

        $response = $this->enrollmentService->save($params);
        $id = $response['id'];
        $originalExpirationDate = new \DateTime('@' . $response['expiration']);

        $addToExpiration = ['days' => 30];
        $response = $this->enrollmentService->extend($id, $addToExpiration);
        $newExpirationDate = new \DateTime('@' . $response['expiration']);
        $this->assertEquals(30, $newExpirationDate->diff($originalExpirationDate)->format('%a'));

        $response = $this->enrollmentService->extend($id, $addToExpiration);
        $newExpirationDate = new \DateTime('@' . $response['expiration']);
        $this->assertEquals(60, $newExpirationDate->diff($originalExpirationDate)->format('%a'));

        $enrollment = $this->enrollmentService->find($id);
        $expirationDate = new \DateTime('@' . $enrollment['expiration']);
        $this->assertEquals(60, $expirationDate->diff($originalExpirationDate)->format('%a'));
    }

    public function testExtendExpirationAlreadyExpired()
    {
        $objects = $this->createRequiredObjectsForEnrollment();
        $params = [
            'userId' => $objects['user']->getId(),
            'examId' => $objects['exam']->getId(),
            'organizationId' => $objects['organization']->getId(),

        ];
        $response = $this->enrollmentService->save($params);
        $id = $response['id'];

        $today = new \DateTime();
        $expirationDate = (new \DateTime())->modify('-1 years');
        $this->enrollmentService->update($id, ['expiration' => $expirationDate->getTimestamp()]);

        $addToExpiration = ['days' => 30];
        $response = $this->enrollmentService->extend($id, $addToExpiration);
        $newExpirationDate = new \DateTime('@' . $response['expiration']);

        $this->assertEquals(30, $newExpirationDate->diff($today)->format('%a'));
    }

    public function testExtendExpirationAlreadyExpired60Days()
    {
        $objects = $this->createRequiredObjectsForEnrollment();
        $params = [
            'userId' => $objects['user']->getId(),
            'examId' => $objects['exam']->getId(),
            'organizationId' => $objects['organization']->getId(),

        ];
        $response = $this->enrollmentService->save($params);
        $id = $response['id'];

        $today = new \DateTime();
        $expirationDate = (new \DateTime())->modify('-1 years');
        $this->enrollmentService->update($id, ['expiration' => $expirationDate->getTimestamp()]);

        $addToExpiration = ['days' => 60];
        $response = $this->enrollmentService->extend($id, $addToExpiration);
        $newExpirationDate = new \DateTime('@' . $response['expiration']);

        $this->assertEquals(60, $newExpirationDate->diff($today)->format('%a'));
    }

    public function testExtendExpirationAlreadyExpired90Days()
    {
        $objects = $this->createRequiredObjectsForEnrollment();
        $params = [
            'userId' => $objects['user']->getId(),
            'examId' => $objects['exam']->getId(),
            'organizationId' => $objects['organization']->getId(),

        ];
        $response = $this->enrollmentService->save($params);
        $id = $response['id'];

        $today = new \DateTime();
        $expirationDate = (new \DateTime())->modify('-1 years');
        $this->enrollmentService->update($id, ['expiration' => $expirationDate->getTimestamp()]);

        $addToExpiration = ['days' => 90];
        $response = $this->enrollmentService->extend($id, $addToExpiration);
        $newExpirationDate = new \DateTime('@' . $response['expiration']);

        $this->assertEquals(90, $newExpirationDate->diff($today)->format('%a'));
    }

    public function testExtendExpirationMissingTimeframeException()
    {
        $objects = $this->createRequiredObjectsForEnrollment();
        $params = [
            'userId' => $objects['user']->getId(),
            'examId' => $objects['exam']->getId(),
            'organizationId' => $objects['organization']->getId(),

        ];
        $response = $this->enrollmentService->save($params);
        $id = $response['id'];

        $this->expectException(\InvalidArgumentException::class);
        $response = $this->enrollmentService->extend($id, []);
    }

    public function testExtendExpirationInvalidTimeframeException()
    {
        $objects = $this->createRequiredObjectsForEnrollment();
        $params = [
            'userId' => $objects['user']->getId(),
            'examId' => $objects['exam']->getId(),
            'organizationId' => $objects['organization']->getId(),

        ];
        $response = $this->enrollmentService->save($params);
        $id = $response['id'];

        $this->expectException(\InvalidArgumentException::class);
        $response = $this->enrollmentService->extend($id, ['days' => 'asdf']);
    }

    /**
     * @dataProvider dataProviderUpdate
     */
    public function testUpdate($id, $params, $errorMessage)
    {

        if (!empty($params['type']) &&
            $params['type'] == Entity\Enrollment::TYPE_TRIAL_CONVERTED) {

            /** @var Entity\Enrollment $enrollmentBefore */
            $enrollmentBefore = $this->getServiceManager()->get('enrollmentRepository')->findOneById($id);

            $this->mailChimpMock->shouldReceive('addSubscriberToList')
                ->with(m::any(), m::type('\Hondros\Api\Client\MailChimp\Subscriber'))
                ->once();

            $this->mailChimpMock->shouldReceive('removeFromList')
                ->with(m::any(), $enrollmentBefore->getUser()->getEmail())
                ->once();
        }

        try {
            $response = $this->enrollmentService->update($id, $params);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e->getMessage());
            $this->assertEquals($errorMessage, $e->getMessage());

            return;
        }

        // clean up before we get started
        $this->getEntityManager()->clear();

        if (!empty($errorMessage)) {
            $this->fail("Shouldn't be here if there is an error message");
        }

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($id, $response['id']);

        // make sure the db really got updated
        if (empty($enrollment)) {
            $enrollment = $this->getServiceManager()->get('enrollmentRepository')->findOneById($id);
        }

        foreach ($params as $key => $value) {
            $this->assertEquals($value, $response[$key]);
            $this->assertEquals($value, $enrollment->{'get' . ucwords($key)}());
        }
    }

    /**
     * make sure all the progress gets setup correctly
     */
    public function testProgressValid()
    {
        $objects = $this->createRequiredObjectsForProgress();
        $user = $objects['user'];
        $exam = $objects['exam'];
        $organization = $objects['organization'];

        $response = $this->enrollmentService->save([
            'userId' => $user->getId(),
            'examId' => $exam->getId(),
            'organizationId' => $organization->getId()
        ]);

        // clean up before we get started
        $this->getEntityManager()->clear();

        /** @var Entity\Enrollment $enrollment */
        $enrollment = $this->getServiceManager()->get('enrollmentRepository')->findOneById($response['id']);

        /** @var Entity\ExamModule[] $examModules */
        $examModules = $this->getServiceManager()->get('examModuleRepository')->findByExamId($enrollment->getExamId());
        $this->assertGreaterThan(0, count($examModules));
        $examModule = $examModules[0];

        /** @var Entity\Question[] $preassessmentQuestions */
        $preassessmentQuestions =
            $this->getServiceManager()->get('questionRepository')->findForModule($examModule->getModule()->getId(), Entity\ModuleQuestion::TYPE_PREASSESSMENT);

        /** @var Entity\Question[] $studyQuestions */
        $studyQuestions =
            $this->getServiceManager()->get('questionRepository')->findForModule($examModule->getModule()->getId(), Entity\ModuleQuestion::TYPE_STUDY);

        /** @var Entity\Question[] $practiceQuestions */
        $practiceQuestions =
            $this->getServiceManager()->get('questionRepository')->findForModule($examModule->getModule()->getId(), Entity\ModuleQuestion::TYPE_PRACTICE);

        /** @var Entity\Question[] $examQuestions */
        $examQuestions =
            $this->getServiceManager()->get('questionRepository')->findForModule($examModule->getModule()->getId(), Entity\ModuleQuestion::TYPE_EXAM);

        /** @var Entity\Progress $studyProgress */
        $studyProgress = $this->getServiceManager()->get('progressRepository')->findByEnrollmentModule(
            $enrollment->getId(), $examModule->getModuleId(), Entity\Progress::TYPE_STUDY);

        /** @var Entity\Progress $practiceProgress */
        $practiceProgress = $this->getServiceManager()->get('progressRepository')->findByEnrollmentModule(
            $enrollment->getId(), $examModule->getModuleId(), Entity\Progress::TYPE_PRACTICE);

        $this->assertEquals($examModule->getModuleId(), $studyProgress->getModuleId());
        $this->assertEquals($examModule->getModuleId(), $practiceProgress->getModuleId());

        $studyProgressQuestions = $studyProgress->getQuestions();
        $practiceProgressQuestions = $practiceProgress->getQuestions();

        $this->assertGreaterThan(0, count($studyProgressQuestions));
        $this->assertGreaterThan(0, count($practiceProgressQuestions));

        $this->assertCount(count($preassessmentQuestions), $practiceProgressQuestions);
        $this->assertCount(count($studyQuestions), $studyProgressQuestions);
        $this->assertCount(count($practiceQuestions), $practiceProgressQuestions);
        $this->assertCount(count($examQuestions), $practiceProgressQuestions);

        $studyQuestionIds = [];
        array_walk($studyQuestions, function (Entity\Question $question) use ($studyQuestionIds) {
            $studyQuestionIds[] = $question->getId();
        });
        sort($studyQuestionIds);

        $studyProgressQuestionsIds = [];
        $studyProgressQuestionsArray = $studyProgressQuestions->toArray();
        array_walk($studyProgressQuestionsArray, function (Entity\ProgressQuestion $question) use ($studyProgressQuestionsIds) {
            $studyProgressQuestionsIds[] = $question->getQuestionId();
        });
        sort($studyProgressQuestionsIds);

        $this->assertEquals($studyQuestionIds, $studyProgressQuestionsIds);

        $practiceQuestionIds = [];
        array_walk($practiceQuestions, function (Entity\Question $question) use ($practiceQuestionIds) {
            $practiceQuestionIds[] = $question->getId();
        });
        sort($practiceQuestionIds);

        $practiceProgressQuestionsIds = [];
        $practiceProgressQuestionsArray = $practiceProgressQuestions->toArray();
        array_walk($practiceProgressQuestionsArray, function (Entity\ProgressQuestion $question) use ($practiceProgressQuestionsIds) {
            $practiceProgressQuestionsIds[] = $question->getQuestionId();
        });
        sort($practiceProgressQuestionsIds);

        $this->assertEquals($practiceQuestionIds, $practiceProgressQuestionsIds);
    }

    /**
     * create some data to test save pending
     * @return array
     */
    public function dataProviderSavePending()
    {
        return [
            'invalid no email' => [[
            ], 'Invalid email.'],

            'invalid bad email' => [[
                'email' => -1
            ], 'Invalid email.'],

            'invalid no first name' => [[
                'email' => 'panda@powa.com'
            ], 'Invalid first name.'],

            'invalid no last name' => [[
                'email' => 'panda@powa.com',
                'firstName' => 'panda'
            ], 'Invalid last name.'],

            'invalid no product ids' => [[
                'email' => 'panda@powa.com',
                'firstName' => 'panda',
                'lastName' => 'powa'
            ], 'Invalid product ids.'],

            'valid' => [[
                'email' => 'panda@powa.com',
                'firstName' => 'panda',
                'lastName' => 'powa',
                'productIds' => '1,2,3'
            ], null]
        ];
    }

    /**
     * @note remember this happens before setup, so nothing in setup is available here
     * @return array [$params, $errorMessage]
     */
    public function dataProviderCreate()
    {
        $objects = $this->createRequiredObjectsForEnrollment();
        $user = $objects['user'];
        $exam = $objects['exam'];
        $organization = $objects['organization'];

        $compucramOrganization = $this->getServiceManager()->get('organizationRepository')
            ->findOneByName(Entity\Organization::COMPUCRAM);

        if (empty($compucramOrganization)) {
            $compucramOrganization = $this->generateOrganization();
            $compucramOrganization->setName(Entity\Organization::COMPUCRAM);
            $this->getEntityManager()->persist($compucramOrganization);
            $this->getEntityManager()->flush();
            $this->getEntityManager()->clear();
        }

        $exam2 = $this->createTestExam();
        $exam3 = $this->createTestExam();
        $exam4 = $this->createTestExam();
        $exam5 = $this->createTestExam();

        return [
            'invalid no user' => [[], 'Invalid user id.'],

            'invalid bad user' => [[
                'userId' => -1
            ], 'User not found.'],

            'invalid no exam' => [[
                'userId' => $user->getId()
            ], 'Invalid exam id.'],

            'invalid bad exam' => [[
                'userId' => $user->getId(),
                'examId' => -1
            ], 'Invalid exam id.'],

            'invalid no organization' => [[
                'userId' => $user->getId(),
                'examId' => $exam->getId()
            ], 'Invalid organization id.'],

            'invalid bad organization' => [[
                'userId' => $user->getId(),
                'examId' => $exam->getId(),
                'organizationId' => -1
            ], 'Invalid organization id.'],

            'invalid bad perpetual' => [[
                'userId' => $user->getId(),
                'examId' => $exam->getId(),
                'organizationId' => $organization->getId(),
                'perpetual' => 'asdf'
            ], 'Perpetual parameter must be of boolean value.'],

            'invalid bad type' => [[
                'userId' => $user->getId(),
                'examId' => $exam->getId(),
                'organizationId' => $organization->getId(),
                'type' => 'asdf'
            ], 'Invalid enrollment type.'],

            'valid minimum' => [[
                'userId' => $user->getId(),
                'examId' => $exam->getId(),
                'organizationId' => $organization->getId()
            ], null],

            'invalid duplicate enrollment' => [[
                'userId' => $user->getId(),
                'examId' => $exam->getId(),
                'organizationId' => $organization->getId()
            ], "User {$user->getId()} already has an enrollment with exam {$exam->getId()}."],

            'valid minimum exam code instead of id and with type trial' => [[
                'userId' => $user->getId(),
                'examCode' => $exam2->getCode(),
                'organizationId' => $organization->getId(),
                'type' => Entity\Enrollment::TYPE_TRIAL
            ], null],

            'valid with perpetual' => [[
                'userId' => $user->getId(),
                'examId' => $exam3->getId(),
                'organizationId' => $organization->getId(),
                'perpetual' => true
            ], null],

            'valid for compucram to test mailchimp email not found' => [[
                'userId' => $user->getId(),
                'examId' => $exam4->getId(),
                'organizationId' => $compucramOrganization->getId()
            ], null],

            'valid for compucram to test mailchimp email found' => [[
                'userId' => $user->getId() . '#found',
                'examId' => $exam5->getId(),
                'organizationId' => $compucramOrganization->getId()
            ], null],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderUpdate()
    {
        $enrollment = $this->createEnrollment(Entity\Enrollment::TYPE_TRIAL);
        $enrollmentFull = $this->createEnrollment(Entity\Enrollment::TYPE_FULL);

        return [
            'invalid no id' => [null, [], 'Invalid id.'],

            'invalid bad id' => [-1, [], 'Invalid id.'],

            'invalid bad type int' => [$enrollment->getId(), [
                'type' => 123123
            ], 'Invalid enrollment type.'],

            'invalid bad type string' => [$enrollment->getId(), [
                'type' => 'asdf'
            ], 'Invalid enrollment type.'],

            'valid trial change properties' => [$enrollment->getId(), [
                'showPreAssessment' => false,
                'externalOrderId' => '123ABC',
                'totalTime' => 1000
            ], null],

            'valid full change properties' => [$enrollmentFull->getId(), [
                'showPreAssessment' => false,
                'externalOrderId' => '123ABC',
                'totalTime' => 1000
            ], null],

            'valid trial to converted for mailchimp' => [$enrollment->getId(), [
                'showPreAssessment' => true,
                'externalOrderId' => '123ABC',
                'totalTime' => 1010,
                'type' => Entity\Enrollment::TYPE_TRIAL_CONVERTED
            ], null]

        ];
    }

    /**
     * create default objects we can use to test
     * @return array
     */
    protected function createRequiredObjectsForEnrollment()
    {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $user = $this->generateUser();
        $industry = $this->generateIndustry();
        $exam = $this->generateExam();
        $exam->setIndustry($industry);
        $organization = $this->generateOrganization();

        $entityManager->persist($user);
        $entityManager->persist($industry);
        $entityManager->persist($exam);
        $entityManager->persist($organization);

        $entityManager->flush();

        // clean up before we get started
        $this->entityManager->clear();

        return [
            'user' => $user,
            'exam' => $exam,
            'organization' => $organization
        ];
    }

    /**
     * create all the assets needed to generate progress for an enrollment.
     *
     * @return array
     * @throws Exception
     */
    protected function createRequiredObjectsForProgress()
    {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();
        $entityManager->getConnection()->beginTransaction();

        $user = $this->generateUser();
        $industry = $this->generateIndustry();
        $exam = $this->generateExam();
        $exam->setIndustry($industry);
        $organization = $this->generateOrganization();

        $entityManager->persist($user);
        $entityManager->persist($industry);
        $entityManager->persist($exam);
        $entityManager->persist($organization);

        // @todo remove all question bank stuff after schema migration
        $questionBankStudy = $this->generateQuestionBank();
        $entityManager->persist($questionBankStudy);

        $studyQuestion1 = $this->generateQuestionWithAnswers();
        $studyQuestion1->setQuestionBank($questionBankStudy);
        $entityManager->persist($studyQuestion1);
        $entityManager->persist($studyQuestion1->getAnswers()[0]);

        $studyQuestion2 = $this->generateQuestionWithAnswers();
        $studyQuestion2->setQuestionBank($questionBankStudy);
        $entityManager->persist($studyQuestion2);
        $entityManager->persist($studyQuestion2->getAnswers()[0]);

        $studyQuestion3 = $this->generateQuestionWithAnswers();
        $studyQuestion3->setQuestionBank($questionBankStudy);
        $entityManager->persist($studyQuestion3);
        $entityManager->persist($studyQuestion3->getAnswers()[0]);

        $questionBankPractice = $this->generateQuestionBank(Entity\QuestionBank::TYPE_PRACTICE);
        $entityManager->persist($questionBankPractice);

        $practiceQuestion1 = $this->generateQuestionWithAnswers(Entity\Question::TYPE_MULTI);
        $practiceQuestion1->setQuestionBank($questionBankPractice);
        $entityManager->persist($practiceQuestion1);
        $entityManager->persist($practiceQuestion1->getAnswers()[0]);
        $entityManager->persist($practiceQuestion1->getAnswers()[1]);
        $entityManager->persist($practiceQuestion1->getAnswers()[2]);
        $entityManager->persist($practiceQuestion1->getAnswers()[3]);

        $practiceQuestion2 = $this->generateQuestionWithAnswers(Entity\Question::TYPE_MULTI);
        $practiceQuestion2->setQuestionBank($questionBankPractice);
        $entityManager->persist($practiceQuestion2);
        $entityManager->persist($practiceQuestion2->getAnswers()[0]);
        $entityManager->persist($practiceQuestion2->getAnswers()[1]);
        $entityManager->persist($practiceQuestion2->getAnswers()[2]);
        $entityManager->persist($practiceQuestion2->getAnswers()[3]);

        $module1 = $this->generateModule();
        $module1->setPreassessmentBank($questionBankPractice)
            ->setStudyBank($questionBankStudy)
            ->setPracticeBank($questionBankPractice)
            ->setExamBank($questionBankPractice)
            ->setIndustry($industry);
        $entityManager->persist($module1);

        $examModule1 = $this->generateExamModule();
        $examModule1->setExam($exam)
            ->setModule($module1);
        $entityManager->persist($examModule1);

        $moduleQuestion1 = $this->generateModuleQuestion(Entity\ModuleQuestion::TYPE_STUDY)
            ->setQuestion($studyQuestion1)
            ->setModule($module1);

        $moduleQuestion2 = $this->generateModuleQuestion(Entity\ModuleQuestion::TYPE_STUDY)
            ->setQuestion($studyQuestion2)
            ->setModule($module1);

        $moduleQuestion3 = $this->generateModuleQuestion(Entity\ModuleQuestion::TYPE_STUDY)
            ->setQuestion($studyQuestion3)
            ->setModule($module1);

        $moduleQuestion4 = $this->generateModuleQuestion(Entity\ModuleQuestion::TYPE_PRACTICE)
            ->setQuestion($practiceQuestion1)
            ->setModule($module1);

        $moduleQuestion5 = $this->generateModuleQuestion(Entity\ModuleQuestion::TYPE_PRACTICE)
            ->setQuestion($practiceQuestion2)
            ->setModule($module1);

        $moduleQuestion6 = $this->generateModuleQuestion(Entity\ModuleQuestion::TYPE_EXAM)
            ->setQuestion($practiceQuestion1)
            ->setModule($module1);

        $moduleQuestion7 = $this->generateModuleQuestion(Entity\ModuleQuestion::TYPE_EXAM)
            ->setQuestion($practiceQuestion2)
            ->setModule($module1);

        $moduleQuestion8 = $this->generateModuleQuestion(Entity\ModuleQuestion::TYPE_PREASSESSMENT)
            ->setQuestion($practiceQuestion1)
            ->setModule($module1);

        $moduleQuestion9 = $this->generateModuleQuestion(Entity\ModuleQuestion::TYPE_PREASSESSMENT)
            ->setQuestion($practiceQuestion2)
            ->setModule($module1);

        $this->getEntityManager()->persist($moduleQuestion1);
        $this->getEntityManager()->persist($moduleQuestion2);
        $this->getEntityManager()->persist($moduleQuestion3);
        $this->getEntityManager()->persist($moduleQuestion4);
        $this->getEntityManager()->persist($moduleQuestion5);
        $this->getEntityManager()->persist($moduleQuestion6);
        $this->getEntityManager()->persist($moduleQuestion7);
        $this->getEntityManager()->persist($moduleQuestion8);
        $this->getEntityManager()->persist($moduleQuestion9);

        try {
            $entityManager->flush();
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            $entityManager->getConnection()->rollBack();
            throw $e;
        }

        // clean up before we get started
        $this->entityManager->clear();

        return [
            'user' => $user,
            'exam' => $exam,
            'organization' => $organization
        ];
    }

    /**
     * @todo move to helper
     * @param int $type
     * @return Entity\Enrollment
     * @throws Exception
     */
    protected function createEnrollment($type = Entity\Enrollment::TYPE_FULL)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->getEntityManager();
        $entityManager->getConnection()->beginTransaction();

        $user = $this->generateUser();
        $organization = $this->generateOrganization();
        $industry = $this->generateIndustry();
        $exam = $this->generateExam();
        $exam->setIndustry($industry);

        $enrollment = new Entity\Enrollment();
        $enrollment->setUser($user)
            ->setExam($exam)
            ->setOrganization($organization)
            ->setStatus(Entity\Enrollment::STATUS_ACTIVE)
            ->setType($type);

        $entityManager->persist($user);
        $entityManager->persist($organization);
        $entityManager->persist($industry);
        $entityManager->persist($exam);
        $entityManager->persist($enrollment);

        try {
            $entityManager->flush();
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();
            throw $e;
        }

        // clean up before we get started
        $this->entityManager->clear();

        return $enrollment;
    }

    /**
     * @todo move to a helper
     * @return Entity\Exam
     */
    protected function createTestExam()
    {
        /**
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = $this->getEntityManager();

        $industry = $this->generateIndustry();
        $exam = $this->generateExam();
        $exam->setIndustry($industry);

        $entityManager->persist($industry);
        $entityManager->persist($exam);

        $entityManager->flush();

        // clean up before we get started
        $this->entityManager->clear();

        return $exam;
    }
}
