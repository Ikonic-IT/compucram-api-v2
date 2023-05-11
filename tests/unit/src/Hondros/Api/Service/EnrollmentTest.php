<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 7/28/19
 * Time: 1:45 PM
 */

namespace Hondros\Unit\Api\Service;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Hondros\Api\Client\MailChimp;
use Hondros\Api\Service\Enrollment;
use Hondros\Api\Service\ExamModule;
use Hondros\Api\Service\Module;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Hondros\Common\DoctrineSingle;
use Mockery as m;
use Hondros\Api\Model\Entity;
use InvalidArgumentException;
use Zend\Config\Config;
use PHPUnit\Framework\TestCase;

class EnrollmentTest extends TestCase
{
    use EntityGeneratorUtil {
    }

    /**
     * @var \Hondros\Api\Service\Enrollment
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
    protected $enrollmentRepositoryMock;

    /**
     * @var \Mockery\Mock
     */
    protected $examRepositoryMock;

    /**
     * @var \Mockery\Mock
     */
    protected $moduleRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManagerMock = m::mock(\Doctrine\ORM\EntityManager::class);
        $this->loggerMock = m::mock(\Monolog\Logger::class);
        $this->enrollmentRepositoryMock = m::mock(\Hondros\Api\Model\Repository\Enrollment::class);
        $this->configMock = m::mock(Config::class);
        $this->examRepositoryMock = m::mock(\Hondros\Api\Model\Repository\Exam::class);
        $this->moduleRepositoryMock = m::mock(\Hondros\Api\Model\Repository\Module::class);
        $this->questionRepositoryMock = m::mock(\Hondros\Api\Model\Repository\Question::class);
        $this->userRepositoryMock = m::mock(\Hondros\Api\Model\Repository\User::class);
        $this->organizationRepositoryMock = m::mock(\Hondros\Api\Model\Repository\Organization::class);
        $this->mailChimpClientMock = m::mock(MailChimp::class);

        $this->service = new Enrollment(
            $this->entityManagerMock,
            $this->loggerMock,
            $this->enrollmentRepositoryMock,
            $this->configMock,
            $this->examRepositoryMock,
            $this->moduleRepositoryMock,
            $this->questionRepositoryMock,
            $this->userRepositoryMock,
            $this->organizationRepositoryMock,
            $this->mailChimpClientMock
        );
    }

    protected function tearDown(): void
    {
        $this->service = null;

        $this->entityManagerMock = null;
        $this->loggerMock = null;
        $this->examModuleRepositoryMock = null;

        m::close();

        parent::tearDown();
    }

    public function testCreateModuleProgressInvalidEnrollmentId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid enrollment id.");
        $this->service->createModuleProgress(null, null, []);
    }

    public function testCreateModuleProgressInvalidModuleId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid module id.");
        $this->service->createModuleProgress(1, null, []);
    }

    public function testCreateModuleProgressEmptyType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid type specified.");
        $this->service->createModuleProgress(1, 1, []);
    }

    public function testCreateModuleProgressInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid type specified.");
        $this->service->createModuleProgress(1, 1, ['type' => 'asdf']);
    }

    public function testCreateModuleProgressEnrollmentNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Enrollment not found.");
        $this->enrollmentRepositoryMock->shouldReceive('find')->with(1)->andReturnNull();
        $this->service->createModuleProgress(1, 1, ['type' => Entity\Progress::TYPE_STUDY]);
    }

    public function testCreateModuleProgressModuleNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Module not found.");
        $this->enrollmentRepositoryMock->shouldReceive('find')->with(1)->andReturn(new Entity\Enrollment());
        $this->moduleRepositoryMock->shouldReceive('find')->with(2)->andReturnNull();
        $this->service->createModuleProgress(1, 2, ['type' => Entity\Progress::TYPE_STUDY]);
    }
}
