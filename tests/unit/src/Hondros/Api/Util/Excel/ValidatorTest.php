<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/18/17
 * Time: 2:20 PM
 */

namespace Hondros\Unit\Api\Util\Excel;

use Hondros\Api\Util\Excel\Validator;
use Hondros\Api\Model\Entity;
use Mockery as m;
use PHPUnit\Framework\TestCase;

/**
 * Class ValidatorTest
 */
class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var \Mockery\Mock
     */
    protected $industryMock;

    /**
     * @var \Mockery\Mock
     */
    protected $moduleMock;

    /**
     * @var \Mockery\Mock
     */
    protected $stateMock;

    /**
     * @var string
     */
    protected $validExamFilePath1 = 'tests/unit/assets/import/Exam_valid1.xlsx';

    /**
     * @var string
     */
    protected $invalidExamFilePath1 = 'tests/unit/assets/import/Exam_invalid1.xlsx';

    /**
     * @var string
     */
    protected $invalidExamFilePath2 = 'tests/unit/assets/import/Exam_invalid2.xlsx';

    /**
     * @var string
     */
    protected $validModuleFilePath1 = 'tests/unit/assets/import/Module_valid1.xlsx';

    /**
     * @var string
     */
    protected $invalidModuleFilePath1 = 'tests/unit/assets/import/Module_invalid1.xlsx';

    protected function setUp(): void
    {
        parent::setUp();

        $this->industryMock = m::mock('Hondros\Api\Model\Repository\Industry');
        $this->stateMock = m::mock('Hondros\Api\Model\Repository\State');
        $this->moduleMock = m::mock('Hondros\Api\Model\Repository\Module');

        $this->validator = new Validator($this->industryMock, $this->stateMock, $this->moduleMock);
    }

    protected function tearDown(): void
    {
        $this->validator = null;
        $this->industryMock = null;
        $this->stateMock = null;
        $this->moduleMock = null;

        m::close();

        parent::tearDown();
    }

    public function testValidateExamFileNotFound()
    {
        $response = $this->validator->validateExamFile('fake.xlsx');
        $this->assertFalse($response->isValid());
        $errors = $response->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('File not found.', $errors[0]);
    }

    public function testValidateExamFileValid()
    {
        $industry = new Entity\Industry();
        $this->industryMock->shouldReceive('findOneByName')->andReturn($industry);
        $this->stateMock->shouldNotReceive('findOneByCode');

        $module = new Entity\Module();
        $this->moduleMock->shouldReceive('findOneByCode')->times(4)->andReturn($module);

        $response = $this->validator->validateExamFile($this->getValidExamFilePath());
        $this->assertTrue($response->isValid());
        $errors = $response->getErrors();
        $this->assertCount(0, $errors);
    }

    public function testValidateExamFileIndustryNotFound()
    {
        $this->industryMock->shouldReceive('findOneByName')->andReturn(null);
        $this->stateMock->shouldNotReceive('findOneByCode');

        $module = new Entity\Module();
        $this->moduleMock->shouldReceive('findOneByCode')->times(4)->andReturn($module);

        $response = $this->validator->validateExamFile($this->getValidExamFilePath());
        $this->assertFalse($response->isValid());
        $errors = $response->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('Industry not found at B4.', $errors[0]);
    }

    public function testValidateExamFileModuleNotFound()
    {
        $industry = new Entity\Industry();
        $this->industryMock->shouldReceive('findOneByName')->andReturn($industry);
        $this->stateMock->shouldNotReceive('findOneByCode');

        $module = new Entity\Module();
        $this->moduleMock->shouldReceive('findOneByCode')->times(3)->andReturn($module);
        $this->moduleMock->shouldReceive('findOneByCode')->andReturn(null);

        $response = $this->validator->validateExamFile($this->getValidExamFilePath());
        $this->assertFalse($response->isValid());
        $errors = $response->getErrors();

        $this->assertCount(1, $errors);
        $this->assertContains('Module not found for code S6OVC at A12.', $errors);
    }

    public function testValidateExamFileMissingAFewThings()
    {
        $this->industryMock->shouldReceive('findOneByName')->andReturn(null);
        $this->stateMock->shouldReceive('findOneByCode')->once()->andReturn(null);

        $module = new Entity\Module();
        $this->moduleMock->shouldReceive('findOneByCode')->times(4)->andReturn($module);

        $response = $this->validator->validateExamFile($this->getInvalidExamFilePath());
        $this->assertFalse($response->isValid());
        $errors = $response->getErrors();

        $this->assertCount(9, $errors);
        $this->assertContains('Invalid id at B1.', $errors);
        $this->assertContains('Invalid industry at B4.', $errors);
        $this->assertContains('State not found at B5.', $errors);
        $this->assertContains('Invalid time in seconds at B6.', $errors);
        $this->assertContains('Invalid practice question count at C9.', $errors);
        $this->assertContains('Invalid module name at B10.', $errors);
        $this->assertContains('Invalid preassessment question count at E11.', $errors);
        $this->assertContains('Invalid exam question count at D10.', $errors);
        $this->assertContains('Invalid exam question count at D12.', $errors);
    }

    public function testValidateExamFileMissingModules()
    {
        $industry = new Entity\Industry();
        $this->industryMock->shouldReceive('findOneByName')->andReturn($industry);
        $this->stateMock->shouldNotReceive('findOneByCode');

        $module = new Entity\Module();
        $this->moduleMock->shouldNotReceive('findOneByCode');

        $response = $this->validator->validateExamFile($this->getInvalidExamFilePath(2));
        $this->assertFalse($response->isValid());
        $errors = $response->getErrors();

        $this->assertCount(1, $errors);
        $this->assertContains('No modules found.', $errors);
    }

    public function testValidateModuleFileNotFound()
    {
        $response = $this->validator->validateModuleFile('fake.xlsx');
        $this->assertFalse($response->isValid());
        $errors = $response->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('File not found.', $errors[0]);
    }

    public function testValidateModuleFileValid()
    {
        $industry = new Entity\Industry();
        $this->industryMock->shouldReceive('findOneByName')->andReturn($industry);
        $this->stateMock->shouldNotReceive('findOneByCode');

        $response = $this->validator->validateModuleFile($this->getValidModuleFilePath());
        $this->assertTrue($response->isValid(), implode(',', $response->getErrors()));
        $errors = $response->getErrors();
        $this->assertCount(0, $errors);
    }

    public function testValidateModuleFileIndustryNotFound()
    {
        $this->industryMock->shouldReceive('findOneByName')->andReturn(null);
        $this->stateMock->shouldNotReceive('findOneByCode');

        $response = $this->validator->validateModuleFile($this->getValidModuleFilePath());
        $this->assertFalse($response->isValid());
        $errors = $response->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('Industry not found at B3 in Details sheet.', $errors[0]);
    }

    public function testValidateModuleFileMissingAFewThings()
    {
        $this->industryMock->shouldReceive('findOneByName')->andReturn(null);
        $this->stateMock->shouldNotReceive('findOneByCode');

        $response = $this->validator->validateModuleFile($this->getInvalidModuleFilePath());
        $this->assertFalse($response->isValid());
        $errors = $response->getErrors();
        
        $this->assertCount(22, $errors, implode(PHP_EOL, $errors));
        $this->assertContains('Invalid industry at B3 in Details sheet.', $errors);
        $this->assertContains('Invalid answer at B5 in StudyQuestions sheet.', $errors);
        $this->assertContains('Invalid question at A7 in StudyQuestions sheet.', $errors);
        $this->assertContains('Invalid duplicate question at row 8 in StudyQuestions sheet.', $errors);
        $this->assertContains('Invalid question at A3 in PracticeQuestions sheet.', $errors);
        $this->assertContains('Invalid correct answer option at G3 in PracticeQuestions sheet.', $errors);
        $this->assertContains('Invalid correct answer option at G6 in PracticeQuestions sheet.', $errors);
        $this->assertContains('Invalid answer at D9 in PracticeQuestions sheet.', $errors);
        $this->assertContains('Invalid answer at E9 in PracticeQuestions sheet.', $errors);
        $this->assertContains('Invalid correct answer option at G10 in PracticeQuestions sheet.', $errors);
        $this->assertContains('Invalid question at A3 in ExamQuestions sheet.', $errors);
        $this->assertContains('Invalid answer at B3 in ExamQuestions sheet.', $errors);
        $this->assertContains('Invalid answer at C3 in ExamQuestions sheet.', $errors);
        $this->assertContains('Invalid correct answer option at G3 in ExamQuestions sheet.', $errors);
        $this->assertContains('Invalid duplicate question at row 6 in ExamQuestions sheet.', $errors);
        $this->assertContains('Invalid correct answer option at G7 in ExamQuestions sheet.', $errors);
        $this->assertContains('Invalid correct answer option at G9 in ExamQuestions sheet.', $errors);
        $this->assertContains('Invalid correct answer option at G10 in ExamQuestions sheet.', $errors);
        $this->assertContains('Invalid answer at C11 in ExamQuestions sheet.', $errors);
        $this->assertContains('Invalid duplicate question at row 12 in ExamQuestions sheet.', $errors);
    }

    /**
     * @param int $index
     * @return string
     */
    protected function getValidExamFilePath($index = 1)
    {
        return getcwd() . DIRECTORY_SEPARATOR . $this->{'validExamFilePath' . $index};
    }

    /**
     * @param int $index
     * @return string
     */
    protected function getInvalidExamFilePath($index = 1)
    {
        return getcwd() . DIRECTORY_SEPARATOR . $this->{'invalidExamFilePath' . $index};
    }

    /**
     * @param int $index
     * @return string
     */
    protected function getValidModuleFilePath($index = 1)
    {
        return getcwd() . DIRECTORY_SEPARATOR . $this->{'validModuleFilePath' . $index};
    }

    /**
     * @param int $index
     * @return string
     */
    protected function getInvalidModuleFilePath($index = 1)
    {
        return getcwd() . DIRECTORY_SEPARATOR . $this->{'invalidModuleFilePath' . $index};
    }
}
