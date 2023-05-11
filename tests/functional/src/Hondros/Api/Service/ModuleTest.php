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
use DateTime;
use Mockery as m;
use Hondros\Test\FunctionalAbstract;
use InvalidArgumentException;

class ModuleTest extends FunctionalAbstract
{
    use FixturesUtil {}

    /**
     * @var \Hondros\Api\Service\Module
     */
    protected $moduleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moduleService = $this->getServiceManager()->get('moduleService');
    }

    protected function tearDown(): void
    {
        $this->moduleService = null;

        m::close();

        parent::tearDown();
    }

    public function testUpdate()
    {
        $industry = $this->generateIndustry();
        $this->getEntityManager()->persist($industry);

        $module = $this->generateModule('panda powa', substr(uniqid(), 0,-10));
        $module->setIndustry($industry);
        $this->getEntityManager()->persist($module);

        $this->getEntityManager()->flush();

        $name = 'joey test 2';
        $code = 'JR02';

        try {
            /** @var \Hondros\Common\DoctrineCollection $response */
            $response = $this->moduleService->update($module->getId(), [
                'name' => $name,
                'code' => $code
            ]);
        } catch (\Exception $e) {
            $this->fail("Should not be in the exception for updating a module.");
        }

        // clean up before we start
        $this->getEntityManager()->clear();

        $this->assertNotEmpty($response);
        $module = $response->getArrayCopy();

        /** @var Entity\Question $questionEntity */
        $moduleEntity = $this->getServiceManager()->get('moduleRepository')->find($module['id']);

        $this->assertEquals($moduleEntity->getName(), $module['name']);
        $this->assertEquals($moduleEntity->getCode(), $module['code']);

        $modifiedFormatted = (new DateTime())->setTimestamp($module['modified'])->format('m-d-Y');
        $this->assertEquals($moduleEntity->getModified()->format('m-d-Y'), $modifiedFormatted);
    }

    public function testFindByCodeInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $uniqueCode = 'JR' . uniqid();
        $this->moduleService->findByCode($uniqueCode);
    }

    public function testFindByCodeValid()
    {
        $industry = $this->createIndustry($this->getEntityManager());
        $uniqueCode = substr('JR' . uniqid(), 0, 10);
        $module = $this->getServiceManager()->get('moduleRepository')->find($uniqueCode);
        $this->assertEmpty($module);

        $date = new DateTime();
        $module = (new Entity\Module())
            ->setCode($uniqueCode)
            ->setName($uniqueCode)
            ->setStatus(Entity\Module::STATUS_ACTIVE)
            ->setIndustry($industry)
            ->setCreated($date)
            ->setModified($date);
        $this->getEntityManager()->persist($module);
        $this->getEntityManager()->flush();

        // clean up before we start
        $this->getEntityManager()->clear();

        $data = $this->moduleService->findByCode($uniqueCode);
        $this->assertNotEmpty($data);
        $this->assertEquals($module->getId(), $data['id']);
        $this->assertEquals($industry->getId(), $data['industryId']);
    }
}
