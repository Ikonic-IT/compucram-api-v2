<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 8/07/18
 * Time: 9:24 PM
 */

namespace Hondros\Functional\Api\Service;

use Hondros\Api\Model\Entity;
use Hondros\Test\Util\Helper\FixturesUtil;
use Mockery as m;
use Hondros\Test\FunctionalAbstract;

class ExamTest extends FunctionalAbstract
{
    use FixturesUtil {}

    /**
     * @var \Hondros\Api\Service\Exam
     */
    protected $examService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->examService = $this->getServiceManager()->get('examService');
    }

    protected function tearDown(): void
    {
        $this->examService = null;

        m::close();

        parent::tearDown();
    }

    public function testUpdate()
    {
        $industry = $this->generateIndustry();
        $this->getEntityManager()->persist($industry);

        $exam = $this->generateExam('panda powa', uniqid());
        $exam->setIndustry($industry);
        $this->getEntityManager()->persist($exam);

        $this->getEntityManager()->flush();

        $name = 'joey test';
        $code = 'JR01';

        try {
            /** @var \Hondros\Common\DoctrineCollection $response */
            $response = $this->examService->update($exam->getId(), [
                'name' => $name,
                'code' => $code
            ]);
        } catch (\Exception $e) {
            $this->fail("Should not be in the exception for updating an exam. {$e->getMessage()}");
        }

        // clean up before we start
        $this->getEntityManager()->clear();

        $this->assertNotEmpty($response);
        $exam = $response->getArrayCopy();

        /** @var Entity\Question $questionEntity */
        $examEntity = $this->getServiceManager()->get('examRepository')->find($exam['id']);

        $this->assertEquals($examEntity->getName(), $exam['name']);
        $this->assertEquals($examEntity->getCode(), $exam['code']);

        $modifiedFormatted = (new \DateTime())->setTimestamp($exam['modified'])->format('m-d-Y');
        $this->assertEquals($examEntity->getModified()->format('m-d-Y'), $modifiedFormatted);
    }

}
