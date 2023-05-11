<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 5/4/17
 * Time: 5:29 PM
 */

namespace Hondros\Unit\Api\Service;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Hondros\Api\Service\Module;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Hondros\Common\DoctrineSingle;
use Mockery as m;
use Hondros\Api\Model\Entity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    use EntityGeneratorUtil {
    }

    /**
     * @var \Hondros\Api\Service\Module
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
    protected $moduleRepositoryMock;

    /**
     * @var \Mockery\Mock
     */
    protected $questionRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManagerMock = m::mock(\Doctrine\ORM\EntityManager::class);
        $this->loggerMock = m::mock(\Monolog\Logger::class);
        $this->moduleRepositoryMock = m::mock(\Hondros\Api\Model\Repository\Module::class);
        $this->questionRepositoryMock = m::mock(\Hondros\Api\Model\Repository\Question::class);

        $this->service = new Module(
            $this->entityManagerMock,
            $this->loggerMock,
            $this->moduleRepositoryMock,
            $this->questionRepositoryMock
        );
    }

    protected function tearDown(): void
    {
        $this->service = null;

        $this->entityManagerMock = null;
        $this->loggerMock = null;
        $this->moduleRepositoryMock = null;
        $this->questionRepositoryMock = null;

        m::close();

        parent::tearDown();
    }

    public function testAddQuestionInvalidModuleIdString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->addQuestion('asdf', 0, 0);
    }

    public function testAddQuestionInvalidModuleType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->addQuestion(1, 'asdf', 0);
    }

    public function testAddQuestionInvalidQuestionIdType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->addQuestion(1, Entity\ModuleQuestion::TYPE_STUDY, 'asdf');
    }

    public function testAddQuestionInvalidModuleId()
    {
        $this->moduleRepositoryMock->shouldReceive('find')->andReturn(null);
        try {
            $this->service->addQuestion(100, Entity\ModuleQuestion::TYPE_STUDY, 1);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('No module found.', $e->getMessage());
            return;
        }

        $this->fail('Should not be here.');
    }

    public function testAddQuestionInvalidQuestionId()
    {
        $this->moduleRepositoryMock->shouldReceive('find')->andReturn($this->generateModule());
        $this->questionRepositoryMock->shouldReceive('find')->andReturn(null);
        try {
            $this->service->addQuestion(100, Entity\ModuleQuestion::TYPE_STUDY, 1);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('No question found.', $e->getMessage());
            return;
        }

        $this->fail('Should not be here.');
    }

    public function testAddQuestionValid()
    {
        $module = $this->generateModule();
        $module->setId(100);

        $question = $this->generateQuestion();
        $question->setId(10);

        $this->moduleRepositoryMock->shouldReceive('find')->andReturn($module);
        $this->questionRepositoryMock->shouldReceive('find')->andReturn($question);
        $this->entityManagerMock->shouldReceive('getReference')
            ->with(Entity\Module::class, $module->getId())->andReturn($module);
        $this->entityManagerMock->shouldReceive('getReference')
            ->with(Entity\Question::class, $question->getId())->andReturn($question);
        $this->entityManagerMock->shouldReceive('persist')->once();
        $this->entityManagerMock->shouldReceive('flush')->once();

        $response = $this->service->addQuestion(100, Entity\ModuleQuestion::TYPE_PRACTICE, 10);
        $this->assertInstanceOf(DoctrineSingle::class, $response);
        $this->assertEquals($module->getId(), $response['moduleId']);
        $this->assertEquals($module->getId(), $response['module']['id']);
        $this->assertEquals($question->getId(), $response['questionId']);
        $this->assertEquals($question->getId(), $response['question']['id']);
        $this->assertEquals(Entity\ModuleQuestion::TYPE_PRACTICE, $response['type']);
    }

    public function testAddQuestionDuplicateError()
    {
        $module = $this->generateModule();
        $module->setId(100);

        $question = $this->generateQuestion();
        $question->setId(10);

        $this->moduleRepositoryMock->shouldReceive('find')->andReturn($module);
        $this->questionRepositoryMock->shouldReceive('find')->andReturn($question);
        $this->loggerMock->shouldReceive('info')->once();

        $this->entityManagerMock->shouldReceive('getReference')
            ->with(Entity\Module::class, $module->getId())->andReturn($module);
        $this->entityManagerMock->shouldReceive('getReference')
            ->with(Entity\Question::class, $question->getId())->andReturn($question);
        $this->entityManagerMock->shouldReceive('persist')->once();

        $exception = m::mock(UniqueConstraintViolationException::class);
        $this->entityManagerMock->shouldReceive('flush')->andThrow($exception);

        try {
            $this->service->addQuestion(100, Entity\ModuleQuestion::TYPE_PRACTICE, 10);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals($e->getMessage(),'This question already exists.');
            return;
        }

        $this->fail("Should not be here.");
    }

    public function testAddQuestionOtherError()
    {
        $module = $this->generateModule();
        $module->setId(100);

        $question = $this->generateQuestion();
        $question->setId(10);

        $this->moduleRepositoryMock->shouldReceive('find')->andReturn($module);
        $this->questionRepositoryMock->shouldReceive('find')->andReturn($question);
        $this->loggerMock->shouldReceive('error')->once();

        $this->entityManagerMock->shouldReceive('getReference')
            ->with(Entity\Module::class, $module->getId())->andReturn($module);
        $this->entityManagerMock->shouldReceive('getReference')
            ->with(Entity\Question::class, $question->getId())->andReturn($question);
        $this->entityManagerMock->shouldReceive('persist')->once();

        $exception = m::mock(\Exception::class);
        $this->entityManagerMock->shouldReceive('flush')->andThrow($exception);

        try {
            $this->service->addQuestion(100, Entity\ModuleQuestion::TYPE_PRACTICE, 10);
        } catch (\Exception $e) {
            $this->assertEquals($e->getMessage(),'Unknown error. Contact support for more information.');
            return;
        }

        $this->fail("Should not be here.");
    }
}
