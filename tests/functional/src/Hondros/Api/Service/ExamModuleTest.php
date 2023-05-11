<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 7/28/19
 * Time: 1:40 PM
 */

namespace Hondros\Functional\Api\Service;

use Hondros\Api\Model\Entity;
use Hondros\Test\Util\Helper\FixturesUtil;
use DateTime;
use Mockery as m;
use Hondros\Test\FunctionalAbstract;

class ExamModuleTest extends FunctionalAbstract
{
    use FixturesUtil {}

    /**
     * @var \Hondros\Api\Service\ExamModule
     */
    protected $examModuleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->examModuleService = $this->getServiceManager()->get('examModuleService');
    }

    protected function tearDown(): void
    {
        $this->examModuleService = null;

        m::close();

        parent::tearDown();
    }

    public function testCreateNew()
    {
        $name = 'JREM';
        $industry = $this->createIndustry($this->getEntityManager());
        $exam = $this->generateExam()->setIndustry($industry);
        $module = $this->generateModule()->setIndustry($industry);
        $this->getEntityManager()->persist($exam);
        $this->getEntityManager()->persist($module);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $response = $this->examModuleService->save($exam->getId(), $module->getId(), [
            'name' => $name,
            'preassessmentQuestions' => 1,
            'practiceQuestions' => 2,
            'examQuestions' => 3,
            'sort' => 1
        ]);

        $this->assertNotEmpty($response['id']);
        $this->assertEquals($name, $response['name']);
        $this->assertEquals($exam->getId(), $response['examId']);
        $this->assertEquals($module->getId(), $response['moduleId']);
    }

    public function testUpdate()
    {
        $name = 'JREM';
        $industry = $this->createIndustry($this->getEntityManager());
        $exam = $this->generateExam()->setIndustry($industry);
        $module = $this->generateModule()->setIndustry($industry);
        $examModule = $this->generateExamModule($name);
        $examModule->setExam($exam);
        $examModule->setModule($module);
        $this->getEntityManager()->persist($exam);
        $this->getEntityManager()->persist($module);
        $this->getEntityManager()->persist($examModule);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        /** @var Entity\ExamModule $examModule */
        $examModule = $this->getServiceManager()->get('examModuleRepository')->findOneBy([
            'examId' => $exam->getId(),
            'moduleId' => $module->getId()
        ]);

        $response = $this->examModuleService->update($exam->getId(), $module->getId(), [
            'name' => 'newone',
            'preassessmentQuestions' => 1,
            'practiceQuestions' => 2,
            'examQuestions' => 3
        ]);

        $this->assertEquals('newone', $response['name']);
        $this->assertEquals(1, $response['preassessmentQuestions']);
        $this->assertEquals(2, $response['practiceQuestions']);
        $this->assertEquals(3, $response['examQuestions']);

        $examModule = $this->getServiceManager()->get('examModuleRepository')->find($response['id']);
        $this->assertEquals('newone', $examModule->getName());
    }

    public function testDelete()
    {
        $this->createExam($this->getEntityManager(), 1);
        $examModules = $this->getServiceManager()->get('examModuleRepository')->findAll();
        $this->assertNotEmpty($examModules);

        $examModule = $examModules[0];
        $id = $examModule->getId();
        $this->examModuleService->delete($examModule->getExamId(), $examModule->getModuleId());

        $response = $this->getServiceManager()->get('examModuleRepository')->find($id);
        $this->assertEmpty($response);
    }
}
