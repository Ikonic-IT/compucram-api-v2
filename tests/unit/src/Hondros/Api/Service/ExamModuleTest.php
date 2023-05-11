<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 7/28/19
 * Time: 1:45 PM
 */

namespace Hondros\Unit\Api\Service;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Hondros\Api\Service\ExamModule;
use Hondros\Api\Service\Module;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Hondros\Common\DoctrineSingle;
use Mockery as m;
use Hondros\Api\Model\Entity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ExamModuleTest extends TestCase
{
    use EntityGeneratorUtil {
    }

    /**
     * @var \Hondros\Api\Service\ExamModule
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
    protected $examModuleRepositoryMock;

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
        $this->examModuleRepositoryMock = m::mock(\Hondros\Api\Model\Repository\ExamModule::class);
        $this->examRepositoryMock = m::mock(\Hondros\Api\Model\Repository\Exam::class);
        $this->moduleRepositoryMock = m::mock(\Hondros\Api\Model\Repository\Module::class);

        $this->service = new ExamModule(
            $this->entityManagerMock,
            $this->loggerMock,
            $this->examModuleRepositoryMock,
            $this->examRepositoryMock,
            $this->moduleRepositoryMock
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

    public function testUpdateMissingExamId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid exam id");
        $this->service->update(null, null, []);
    }

    public function testUpdateAlphaExamId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid exam id asdf");
        $this->service->update('asdf', null, []);
    }

    public function testUpdateMissingModuleId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid module id");
        $this->service->update(123, null, []);
    }

    public function testUpdateAlphaModuleId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid module id asdf");
        $this->service->update(123, 'asdf', []);
    }

    public function testUpdateExamModuleNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("ExamModule not found for 123 456");
        $examId = 123;
        $moduleId = 456;
        $this->examModuleRepositoryMock->shouldReceive('findOneBy')->withArgs([[
            'exam' => $examId,
            'module' => $moduleId
        ]])->andReturnNull()->once();

        $this->service->update($examId, $moduleId, []);
    }

    public function testCreateNewMissingExamId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid exam id");
        $this->service->save(null, null, []);
    }

    public function testCreateNewAlphaExamId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid exam id");
        $this->service->save('asdf', null, []);
    }

    public function testCreateNewMissingModuleId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid module id");
        $this->service->save(123, null, []);
    }

    public function testCreateNewAlphaModuleId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid module id");
        $this->service->save(123, 'asdf', []);
    }

    public function testCreateNewMissingName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Name is required");
        $this->service->save(123, 456, []);
    }

    public function testCreateNewMissingPreassessmentQuestions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid preassessment questions");
        $this->service->save(123, 456, [
            'name' => 'adsf'
        ]);
    }

    public function testCreateNewMissingPracticeQuestions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid practice questions");
        $this->service->save(123, 456, [
            'name' => 'adsf',
            'preassessmentQuestions' => 1
        ]);
    }

    public function testCreateNewMissingExamQuestions()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid exam questions");
        $this->service->save(123, 456, [
            'name' => 'adsf',
            'preassessmentQuestions' => 1,
            'practiceQuestions' => 2
        ]);
    }

    public function testCreateNewMissingSort()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid sort");
        $this->service->save(123, 456, [
            'name' => 'adsf',
            'preassessmentQuestions' => 1,
            'practiceQuestions' => 2,
            'examQuestions' => 3
        ]);
    }

    public function testCreateNewExamNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Exam not found for id 123");
        $examId = 123;
        $moduleId = 456;
        $this->examRepositoryMock->shouldReceive('find')->withArgs([$examId])->andReturnNull()->once();

        $this->service->save($examId, $moduleId, [
            'name' => 'adsf',
            'preassessmentQuestions' => 1,
            'practiceQuestions' => 2,
            'examQuestions' => 3,
            'sort' => 4
        ]);
    }

    public function testCreateNewModuleNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Module not found for id 456");
        $examId = 123;
        $moduleId = 456;
        $this->examRepositoryMock->shouldReceive('find')->withArgs([$examId])->andReturn(new Entity\Module())->once();
        $this->moduleRepositoryMock->shouldReceive('find')->withArgs([$moduleId])->andReturnNull()->once();

        $this->service->save($examId, $moduleId, [
            'name' => 'adsf',
            'preassessmentQuestions' => 1,
            'practiceQuestions' => 2,
            'examQuestions' => 3,
            'sort' => 4
        ]);
    }

    public function testDeleteInvalidExamId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid exam id");
        $this->service->delete(null, null);
    }

    public function testDeleteInvalidModuleId()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid module id");
        $this->service->delete(1, null);
    }

    public function testDeleteNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("ExamModule not found for 1 1");
        $this->examModuleRepositoryMock->shouldReceive('findOneBy')->withArgs([[
            'exam' => 1,
            'module' => 1
        ]])->andReturnNull();
        $this->service->delete(1, 1);
    }
}
