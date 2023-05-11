<?php

namespace Hondros\Functional\Api\Service;

use Hondros\Test\FunctionalAbstract;
use Hondros\Test\Util\Helper\FixturesUtil;

class ProgressTest extends FunctionalAbstract
{
    use FixturesUtil {
    }

    /**
     * @var \Hondros\Api\Service\Progress
     */
    protected $progressService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->progressService = $this->getServiceManager()->get('progressService');
    }

    protected function tearDown(): void
    {
        $this->progressService = null;

        parent::tearDown();
    }

    /**
     * recalculateBasedOnProgressQuestion - verify error for unknown question
     */
    public function test_recalculateBasedOnProgressQuestion_UnknownQuestion()
    {
        // $this->markTestIncomplete(
        //     'This test has not been implemented yet.'
        // );
        
        //setup
        $progressQuestionId = 99999;
        $expectedException = "No progress found for {$progressQuestionId}";

        //run
        try {
            $result = $this->progressService->recalculateBasedOnProgressQuestion($progressQuestionId);
        } catch (\Exception $e) {
            //verify
            $ex = $e->getMessage();
            $this->assertStringContainsStringIgnoringCase($expectedException, $ex);
            return;
        }

        $this->fail("No exception thrown! Expected: {$expectedException}");
    }

    /**
     * recalculateBasedOnProgressQuestion - Verify recalculation
     */
    public function test_recalculateBasedOnProgressQuestion()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
    
    /**
     * getReadinessScore - Verify error for null enrollment id
     */
    public function test_getReadinessScore_NullEnrollmentId()
    {
        //setup
        $studentId = 1;
        $enrollmentId = null;
        $params = null;
        $expectedException = "Empty student id or enrollment id";

        //run
        try {
            $result = $this->progressService->getReadinessScore($studentId, $enrollmentId, $params);
        } catch (\Exception $e) {
            //verify
            $ex = $e->getMessage();
            $this->assertStringContainsStringIgnoringCase($expectedException, $ex);
            return;
        }

        $this->fail("No exception thrown! Expected: {$expectedException}");
    }

    /**
     * getReadinessScore - Verify error for null student id
     */
    public function test_getReadinessScore_NullStudentId()
    {
        //setup
        $studentId = null;
        $enrollmentId = 1;
        $params = null;
        $expectedException = "Empty student id or enrollment id";

        //run
        try {
            $result = $this->progressService->getReadinessScore($studentId, $enrollmentId, $params);
        } catch (\Exception $e) {
            //verify
            $ex = $e->getMessage();
            $this->assertStringContainsStringIgnoringCase($expectedException, $ex);
            return;
        }

        $this->fail("No exception thrown! Expected: {$expectedException}");
    }

    /**
     * getReadinessScore - Verify error for mismatching student
     */
    public function test_getReadinessScore_MismatchingStudent()
    {
        //setup
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $studentId = $enrollment->getUser()->getId() + 1;
        $enrollmentId = $enrollment->getId();
        $params = null;
        $expectedException = "Enrollment $enrollmentId does not belong to student $studentId";

        //run
        try {
            $result = $this->progressService->getReadinessScore($studentId, $enrollmentId, $params);
        } catch (\Exception $e) {
            //verify
            $ex = $e->getMessage();
            $this->assertStringContainsStringIgnoringCase($expectedException, $ex);
            return;
        }

        $this->fail("No exception thrown! Expected: {$expectedException}");
    }
    
    /**
     * getReadinessScore - Verify no errors when there are no scores
     */
    public function test_getReadinessScore_NoScores()
    {
        //setup
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $enrollmentId = $enrollment->getId();
        $studentId = $enrollment->getUser()->getId();
        $params = null;

        //run
        try {
            $result = $this->progressService->getReadinessScore($studentId, $enrollmentId, $params);
        } catch (\Exception $e) {
            //display exception message then fail test
            $ex = $e->getMessage();
            $this->fail("Exception: $ex");
            return;
        }

        //verify
        $this->assertEquals($studentId, $result['studentId']);
        $this->assertEquals($enrollmentId, $result['enrollmentId']);
        $this->assertEmpty($result['externalOrderId']);
        $this->assertEquals(0, $result['readinessScore']);
    }

    /**
     * getReadinessScore - Verify score
     */
    public function test_getReadinessScore()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * getEnrollmentScoreCardMetrics - Verify error for null enrollment id
     */
    public function test_getEnrollmentScoreCardMetrics_NullEnrollmentId()
    {
        //setup
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $enrollmentId = null;
        $studentId = $enrollment->getUser()->getId();
        $params = null;
        $expectedException = "Empty student id or enrollment id";

        //run
        try {
            $result = $this->progressService->getEnrollmentScoreCardMetrics($studentId, $enrollmentId, $params);
        } catch (\Exception $e) {
            //verify
            $ex = $e->getMessage();
            $this->assertStringContainsStringIgnoringCase($expectedException, $ex);
            return;
        }

        $this->fail("No exception thrown! Expected: {$expectedException}");
    }

    /**
     * getEnrollmentScoreCardMetrics - Verify error for null student id
     */
    public function test_getEnrollmentScoreCardMetrics_NullStudentId()
    {
        //setup
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $enrollmentId = $enrollment->getId();
        $studentId = null;
        $params = null;
        $expectedException = "Empty student id or enrollment id";

        //run
        try {
            $result = $this->progressService->getEnrollmentScoreCardMetrics($studentId, $enrollmentId, $params);
        } catch (\Exception $e) {
            //verify
            $ex = $e->getMessage();
            $this->assertStringContainsStringIgnoringCase($expectedException, $ex);
            return;
        }

        $this->fail("No exception thrown! Expected: {$expectedException}");
    }

    /**
     * getEnrollmentScoreCardMetrics - Verify error for mismatching student
     */
    public function test_getEnrollmentScoreCardMetrics_MismatchingStudent()
    {
        //setup
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $enrollmentId = $enrollment->getId();
        $studentId = $enrollment->getUser()->getId() + 1;
        $params = null;
        $expectedException = "Enrollment $enrollmentId does not belong to student $studentId";

        //run
        try {
            $result = $this->progressService->getEnrollmentScoreCardMetrics($studentId, $enrollmentId, $params);
        } catch (\Exception $e) {
            //verify
            $ex = $e->getMessage();
            $this->assertStringContainsStringIgnoringCase($expectedException, $ex);
            return;
        }

        $this->fail("No exception thrown! Expected: {$expectedException}");
    }

    /**
     * getEnrollmentScoreCardMetrics  Verify no progress
     */
    public function test_getEnrollmentScoreCardMetrics_NoProgress()
    {
        //setup
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $enrollmentId = $enrollment->getId();
        $studentId = $enrollment->getUser()->getId();
        $params = null;

        //run
        try {
            $result = $this->progressService->getEnrollmentScoreCardMetrics($studentId, $enrollmentId, $params);
        } catch (\Exception $e) {
            //display exception message then fail test
            $ex = $e->getMessage();
            $this->fail("Exception: $ex");
            return;
        }

        //verify
        $this->assertEquals($enrollmentId, $result['enrollmentId']);
        $this->assertEmpty($result['externalOrderId']);
        $this->assertEmpty($result['examScoreAverage']);
        $this->assertEquals(0, $result['questionBankPercentCorrect']);
        $this->assertEquals(0, $result['hoursStudied']);
        $this->assertEquals(0, $result['readinessScore']);
        $this->assertIsArray($result['examCategoryScores']);
        $this->assertCount(0, $result['examCategoryScores']);
        $this->assertIsArray($result['simulatedExamScores']);
        $this->assertCount(0, $result['simulatedExamScores']);
    }

    /**
     * getEnrollmentScoreCardMetrics - Verify values
     */
    public function test_getEnrollmentScoreCardMetrics()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * getStudentScoreCardMetrics - Verify error for no enrollments
     */
    public function test_getStudentScoreCardMetrics_NoEnrollments()
    {
        //setup
        $studentId = 99999;
        $params = null;
        $expectedException = "Unable to find enrollments for student {$studentId}";

        //run
        try {
            $result = $this->progressService->getStudentScoreCardMetrics($studentId, $params);
        } catch (\Exception $e) {
            //verify
            $ex = $e->getMessage();
            $this->assertStringContainsStringIgnoringCase($expectedException, $ex);
            return;
        }

        $this->fail("No exception thrown! Expected: {$expectedException}");
    }

    /**
     * getStudentScoreCardMetrics - Verify no progress
     */
    public function test_getStudentScoreCardMetrics_NoProgress()
    {
        //setup
        $enrollment = $this->createEnrollment($this->getEntityManager());
        $enrollmentId = $enrollment->getId();
        $studentId = $enrollment->getUser()->getId();
        $params = null;

        //run
        try {
            $result = $this->progressService->getStudentScoreCardMetrics($studentId, $params);
        } catch (\Exception $e) {
            //display exception message then fail test
            $ex = $e->getMessage();
            $this->fail("Exception: $ex");
            return;
        }

        //verify
        $this->assertCount(1, $result);
        $this->assertEquals($enrollmentId, $result[0]['enrollmentId']);
        $this->assertEmpty($result[0]['externalOrderId']);
        $this->assertEmpty($result[0]['examScoreAverage']);
        $this->assertEquals(0, $result[0]['questionBankPercentCorrect']);
        $this->assertEquals(0, $result[0]['hoursStudied']);
        $this->assertEquals(0, $result[0]['readinessScore']);
        $this->assertIsArray($result[0]['examCategoryScores']);
        $this->assertCount(0, $result[0]['examCategoryScores']);
    }

    /**
     * getEnrollmentScoreCardMetrics - Verify values
     */
    public function test_getStudentScoreCardMetrics()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
