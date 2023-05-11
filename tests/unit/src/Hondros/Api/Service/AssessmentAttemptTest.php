<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 2/11/18
 * Time: 2:30 PM
 */

namespace Hondros\Unit\Api\Service;

use Hondros\Api\Service\AssessmentAttempt;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Mockery as m;
use Hondros\Api\Model\Entity;
use DateTime;
use DateInterval;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AssessmentAttemptTest extends TestCase
{
    use EntityGeneratorUtil {
    }

    /**
     * @var \Hondros\Api\Service\AssessmentAttempt
     */
    protected $service;

    /**
     * @var \Mockery\Mock
     */
    protected $entityManagerMock;

    /**
     * @var \Mockery\Mock
     */
    protected $loggerMock;

    /**
     * @var \Mockery\Mock
     */
    protected $assessmentAttemptRepositoryMock;

    /**
     * @var \Mockery\Mock
     */
    protected $enrollmentRepository;

    /**
     * @var \Mockery\Mock
     */
    protected $examModuleRepository;

    /**
     * @var \Mockery\Mock
     */
    protected $questionRepository;

    /**
     * @var \Mockery\Mock
     */
    protected $progressRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManagerMock = m::mock(\Doctrine\ORM\EntityManager::class);
        $this->loggerMock = m::mock(\Monolog\Logger::class);
        $this->assessmentAttemptRepositoryMock = m::mock(\Hondros\Api\Model\Repository\AssessmentAttempt::class);
        $this->enrollmentRepository =  m::mock(\Hondros\Api\Model\Repository\Enrollment::class);
        $this->examModuleRepository =  m::mock(\Hondros\Api\Model\Repository\ExamModule::class);
        $this->questionRepository =  m::mock(\Hondros\Api\Model\Repository\Question::class);
        $this->progressRepository =  m::mock(\Hondros\Api\Model\Repository\Progress::class);

        $this->service = new AssessmentAttempt(
            $this->entityManagerMock,
            $this->loggerMock,
            $this->assessmentAttemptRepositoryMock,
            $this->enrollmentRepository,
            $this->examModuleRepository,
            $this->questionRepository,
            $this->progressRepository
        );
    }

    protected function tearDown(): void
    {
        $this->service = null;

        $this->entityManagerMock = null;
        $this->loggerMock = null;
        $this->assessmentAttemptRepositoryMock = null;
        $this->enrollmentRepository = null;
        $this->examModuleRepository = null;
        $this->questionRepository = null;
        $this->progressRepository = null;

        m::close();

        parent::tearDown();
    }

    public function testUpdateNoId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->update(null, []);
    }

    /**
     * make sure completed is not less than created
     */
    public function testAddQuestionOtherError()
    {
        $hourAgo = (new DateTime())->sub(new DateInterval('PT1H'));
        $assessmentAttempt = (new Entity\AssessmentAttempt())
            ->setQuestionCount(100)
            ->setCreated($hourAgo);

        $this->assessmentAttemptRepositoryMock->shouldReceive('find')->andReturn($assessmentAttempt);

        $this->entityManagerMock->shouldReceive('persist')->once();
        $this->entityManagerMock->shouldReceive('flush')->andReturn(null);

        $yesterday = (new DateTime())->sub(new DateInterval('P1D'));
        $assessmentAttemptArray = $this->service->update(1, ['id' => 1, 'completed' => $yesterday->getTimestamp()]);

        $this->assertTrue((new DateTime()) > $yesterday);
        $this->assertTrue($assessmentAttemptArray['completed'] >= $assessmentAttemptArray['created']);
    }
}
