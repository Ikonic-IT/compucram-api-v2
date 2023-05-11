<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/18/17
 * Time: 2:20 PM
 */

namespace Hondros\Unit\Api\Util\Excel;

use Hondros\Api\Util\Excel\ExamToExcel;
use Hondros\Api\Util\Helper\EntityGeneratorUtil;
use Hondros\Api\Util\Helper\QuestionUtil;
use Hondros\Api\Model\Entity;
use Hondros\Api\Util\Helper\StringUtil;
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExamToExcelTest extends TestCase
{
    use EntityGeneratorUtil {}
    use QuestionUtil { getCorrectAnswerLetter as protected; }
    use StringUtil { incrementLetter as protected; }

    /**
     * @var \Hondros\Api\Util\Excel\ExamToExcel
     */
    protected $examToExcel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->examToExcel = new ExamToExcel();
    }

    protected function tearDown(): void
    {
        unset($this->examToExcel);

        parent::tearDown();
    }

    /**
     * create an exam with three modules and make sure the excel is correct and in the
     * right order
     * @param \Hondros\Api\Model\Entity\Exam $exam
     * @param \Hondros\Api\Model\Entity\ExamModule[] $examModules
     * @param \Hondros\Api\Model\Entity\Industry $industry
     * @param \Hondros\Api\Model\Entity\State $state
     *
     * @dataProvider examSheetTestDataProvider
     */
    public function testCreateFileForExam($exam, $examModules, $industry, $state)
    {
        $exam->setIndustry($industry);
        $exam->setState($state);

        $response = $this->examToExcel->createFileForExam($exam, $examModules);
        $this->assertInstanceOf('\Hondros\Api\Util\Excel\Response', $response);
        $this->assertTrue($response->isValid());
        $filePath = $response->getFilePath();
        $this->assertFileExists($filePath);

        $excel = IOFactory::load($filePath);
        $sheet = $excel->getSheet(0);
        $this->assertEquals('Details', $sheet->getTitle());

        // exam labels
        $this->assertEquals('Id', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Title', $sheet->getCell('A2')->getValue());
        $this->assertEquals('Description', $sheet->getCell('A3')->getValue());
        $this->assertEquals('Industry', $sheet->getCell('A4')->getValue());
        $this->assertEquals('State Abbr', $sheet->getCell('A5')->getValue());
        $this->assertEquals('Exam Time', $sheet->getCell('A6')->getValue());

        // exam values
        $this->assertEquals($exam->getCode(), $sheet->getCell('B1')->getValue());
        $this->assertEquals($exam->getName(), $sheet->getCell('B2')->getValue());
        $this->assertEquals($exam->getDescription(), $sheet->getCell('B3')->getValue());
        $this->assertEquals($exam->getIndustry() ? $exam->getIndustry()->getName() : null,
            $sheet->getCell('B4')->getValue());
        $this->assertEquals($exam->getState() ? $exam->getState()->getCode() : null,
            $sheet->getCell('B5')->getValue());
        $this->assertEquals($exam->getExamTime() / 60, $sheet->getCell('B6')->getValue());

        // exam module labels
        $this->assertEquals('Category Id', $sheet->getCell('A8')->getValue());
        $this->assertEquals('Category Name', $sheet->getCell('B8')->getValue());
        $this->assertEquals('Practice Select Count', $sheet->getCell('C8')->getValue());
        $this->assertEquals('Exam Select Count', $sheet->getCell('D8')->getValue());
        $this->assertEquals('Pre-Assessment Select Count', $sheet->getCell('E8')->getValue());

        // exam module values
        if (!empty($examModules)) {
            $this->assertEquals($examModules[0]->getModule()->getCode(), $sheet->getCell('A9')->getValue());
            $this->assertEquals($examModules[0]->getName(), $sheet->getCell('B9')->getValue());
            $this->assertEquals($examModules[0]->getPracticeQuestions(), $sheet->getCell('C9')->getValue());
            $this->assertEquals($examModules[0]->getExamQuestions(), $sheet->getCell('D9')->getValue());
            $this->assertEquals($examModules[0]->getPreassessmentQuestions(), $sheet->getCell('E9')->getValue());

            $this->assertEquals($examModules[2]->getModule()->getCode(), $sheet->getCell('A10')->getValue());
            $this->assertEquals($examModules[2]->getName(), $sheet->getCell('B10')->getValue());
            $this->assertEquals($examModules[2]->getPracticeQuestions(), $sheet->getCell('C10')->getValue());
            $this->assertEquals($examModules[2]->getExamQuestions(), $sheet->getCell('D10')->getValue());
            $this->assertEquals($examModules[2]->getPreassessmentQuestions(), $sheet->getCell('E10')->getValue());

            $this->assertEquals($examModules[1]->getModule()->getCode(), $sheet->getCell('A11')->getValue());
            $this->assertEquals($examModules[1]->getName(), $sheet->getCell('B11')->getValue());
            $this->assertEquals($examModules[1]->getPracticeQuestions(), $sheet->getCell('C11')->getValue());
            $this->assertEquals($examModules[1]->getExamQuestions(), $sheet->getCell('D11')->getValue());
            $this->assertEquals($examModules[1]->getPreassessmentQuestions(), $sheet->getCell('E11')->getValue());
        }

        unlink($filePath);
    }

    /**
     * @param \Hondros\Api\Model\Entity\Module $module
     * @param $studyQuestions
     * @param $practiceQuestions
     * @param $examQuestions
     * @param \Hondros\Api\Model\Entity\Industry $industry
     * @param \Hondros\Api\Model\Entity\State $state
     * @dataProvider moduleSheetTestDataProvider
     */
    public function testCreateFileForModule($module, $studyQuestions, $practiceQuestions, $examQuestions, $industry,
                                            $state)
    {
        $module->setIndustry($industry);
        $module->setState($state);

        $response = $this->examToExcel->createFileForModule($module, $studyQuestions, $practiceQuestions,
            $examQuestions);
        $this->assertInstanceOf('\Hondros\Api\Util\Excel\Response', $response);
        $this->assertTrue($response->isValid());
        $filePath = $response->getFilePath();
        $this->assertFileExists($filePath);

        $excel = IOFactory::load($filePath);
        // make sure there are 4 total sheets
        $sheets = $excel->getAllSheets();
        $this->assertCount(4, $sheets);

        // study
        $sheet = $excel->getSheet(0);
        $this->assertEquals('Details', $sheet->getTitle());

        // module labels
        $this->assertEquals('Id', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Title', $sheet->getCell('A2')->getValue());
        $this->assertEquals('Industry', $sheet->getCell('A3')->getValue());
        $this->assertEquals('State Abbr', $sheet->getCell('A4')->getValue());

        // module values
        $this->assertEquals($module->getCode(), $sheet->getCell('B1')->getValue());
        $this->assertEquals($module->getName(), $sheet->getCell('B2')->getValue());
        $this->assertEquals($module->getIndustry() ? $module->getIndustry()->getName() : null,
            $sheet->getCell('B3')->getValue());
        $this->assertEquals($module->getState() ? $module->getState()->getCode() : null,
            $sheet->getCell('B4')->getValue());

        $sheet = $sheets[1];
        $this->assertEquals('StudyQuestions', $sheet->getTitle());
        $this->assertEquals('Term', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Definition', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Active', $sheet->getCell('C1')->getValue());

        $currentIndex = 2;
        /** @var Entity\Question $studyQuestion */
        foreach ($studyQuestions as $studyQuestion) {
            $this->assertEquals($studyQuestion->getQuestionText(), $sheet->getCell("A{$currentIndex}")->getValue());
            $this->assertEquals($studyQuestion->getAnswers()[0]->getAnswerText(),
                $sheet->getCell("B{$currentIndex}")->getValue());
            $this->assertEquals($studyQuestion->getActive(), $sheet->getCell("C{$currentIndex}")->getValue());
            $currentIndex++;
        }

        // probably good to make sure all questions where added
        if (!empty($studyQuestion)) {
            $this->assertEquals(4, $currentIndex);
        }

        // practice
        $sheet = $sheets[2];
        $currentIndex = 2;
        $maxNumberOfAnswers = 4;
        $startAnswerLetter = 'B';

        /** @var Entity\Question $practiceQuestion */
        foreach ($practiceQuestions as $practiceQuestion) {
            $totalAnswers = count($practiceQuestion->getAnswers());
            if ($totalAnswers > $maxNumberOfAnswers) {
                $maxNumberOfAnswers = $totalAnswers;
            }
        }

        /** @var Entity\Question $practiceQuestion */
        foreach ($practiceQuestions as $practiceQuestion) {
            $this->assertEquals($practiceQuestion->getQuestionText(), $sheet->getCell("A{$currentIndex}")->getValue());

            $nextLetter = $startAnswerLetter;

            for ($x = 0; $x < $maxNumberOfAnswers; $x++) {
                $this->assertEquals($practiceQuestion->getAnswers()[$x]->getAnswerText(),
                    $sheet->getCell("{$this->incrementLetter($startAnswerLetter, $x)}{$currentIndex}")->getValue());

                $nextLetter = $this->incrementLetter($nextLetter);
            }

            $correctAnswer = $this->getCorrectAnswerLetter($practiceQuestion->getAnswers());
            $this->assertEquals($practiceQuestion->getFeedback(), $sheet->getCell("{$this->incrementLetter($nextLetter, 0)}{$currentIndex}")->getValue());
            $this->assertEquals($correctAnswer, $sheet->getCell("{$this->incrementLetter($nextLetter, 1)}{$currentIndex}")->getValue());
            $this->assertEquals($practiceQuestion->getTechniques(), $sheet->getCell("{$this->incrementLetter($nextLetter, 2)}{$currentIndex}")->getValue());
            // skip i
            $this->assertEquals($practiceQuestion->getActive(), $sheet->getCell("{$this->incrementLetter($nextLetter, 4)}{$currentIndex}")->getValue());

            $currentIndex++;
        }

        // probably good to make sure all questions where added
        if (!empty($practiceQuestions)) {
            $this->assertEquals(3, $currentIndex);
        }

        $this->assertEquals('PracticeQuestions', $sheet->getTitle());
        $this->assertEquals('Question', $sheet->getCell('A1')->getValue());

        for ($x = 0; $x < $maxNumberOfAnswers; $x++) {
            $this->assertEquals('Answer ' . $this->incrementLetter($startAnswerLetter, $x - 1), $sheet->getCell("{$this->incrementLetter($startAnswerLetter, $x)}1")->getValue());
        }

        $this->assertEquals('Explanation', $sheet->getCell("{$this->incrementLetter($startAnswerLetter, $maxNumberOfAnswers)}1")->getValue());
        $this->assertEquals('Correct Answer', $sheet->getCell("{$this->incrementLetter($startAnswerLetter, $maxNumberOfAnswers + 1)}1")->getValue());
        $this->assertEquals('Supporting Info', $sheet->getCell("{$this->incrementLetter($startAnswerLetter, $maxNumberOfAnswers + 2)}1")->getValue());
        $this->assertEquals('Tips', $sheet->getCell("{$this->incrementLetter($startAnswerLetter, $maxNumberOfAnswers + 3)}1")->getValue());
        $this->assertEquals('Active', $sheet->getCell("{$this->incrementLetter($startAnswerLetter, $maxNumberOfAnswers + 4)}1")->getValue());

        // exams
        $sheet = $sheets[3];
        $this->assertEquals('ExamQuestions', $sheet->getTitle());
        $this->assertEquals('Question', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Answer A', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Answer B', $sheet->getCell('C1')->getValue());
        $this->assertEquals('Answer C', $sheet->getCell('D1')->getValue());
        $this->assertEquals('Answer D', $sheet->getCell('E1')->getValue());
        $this->assertEquals('Explanation', $sheet->getCell('F1')->getValue());
        $this->assertEquals('Correct Answer', $sheet->getCell('G1')->getValue());
        $this->assertEquals('Supporting Info', $sheet->getCell('H1')->getValue());
        $this->assertEquals('Tips', $sheet->getCell('I1')->getValue());
        $this->assertEquals('Active', $sheet->getCell('J1')->getValue());

        $currentIndex = 2;
        /** @var Entity\Question $examQuestion */
        foreach ($examQuestions as $examQuestion) {
            $this->assertEquals($examQuestion->getQuestionText(), $sheet->getCell("A{$currentIndex}")->getValue());
            $this->assertEquals($examQuestion->getAnswers()[0]->getAnswerText(),
                $sheet->getCell("B{$currentIndex}")->getValue());
            $this->assertEquals($examQuestion->getAnswers()[1]->getAnswerText(),
                $sheet->getCell("C{$currentIndex}")->getValue());
            $this->assertEquals($examQuestion->getAnswers()[2]->getAnswerText(),
                $sheet->getCell("D{$currentIndex}")->getValue());
            $this->assertEquals($examQuestion->getAnswers()[3]->getAnswerText(),
                $sheet->getCell("E{$currentIndex}")->getValue());
            $this->assertEquals($examQuestion->getFeedback(), $sheet->getCell("F{$currentIndex}")->getValue());
            $this->assertEquals($this->getCorrectAnswerLetter($examQuestion->getAnswers()),
                $sheet->getCell("G{$currentIndex}")->getValue());
            $this->assertEquals($examQuestion->getTechniques(), $sheet->getCell("H{$currentIndex}")->getValue());
            $this->assertEquals($examQuestion->getActive(), $sheet->getCell("J{$currentIndex}")->getValue());
            $currentIndex++;
        }

        // probably good to make sure all questions where added
        if (!empty($examQuestions)) {
            $this->assertEquals(5, $currentIndex);
        }

        unlink($filePath);
    }

    /**
     * creates some good date for tests
     * @return array
     */
    public function examSheetTestDataProvider()
    {
        $industry = $this->generateIndustry();
        $state = $this->generateState();

        $examModules = [];

        $module = $this->generateModule();
        $examModule = $this->generateExamModule();
        $examModule->setModule($module);
        $examModules[] = $examModule;

        $module = $this->generateModule('second module', 'SEMO', 'another test');
        $examModule = $this->generateExamModule('Name override2', 1, 2, 3, 2);
        $examModule->setModule($module);
        $examModules[] = $examModule;

        $module = $this->generateModule('third module', 'THR', 'should show second');
        $examModule = $this->generateExamModule('Show second3', 3, 6, 9, 1);
        $examModule->setModule($module);
        $examModules[] = $examModule;

        $exam = $this->generateExam();

        return [
            'with industry and state' => [$exam, $examModules, $industry, $state],
            'with industry' => [$exam, $examModules, $industry, null],
            'with state' => [$exam, $examModules, null, $state],
            'with no industry or state' => [$exam, $examModules, null, null],
            'no modules' => [$exam, [], null, null]
        ];
    }

    /**
     * creates some good date for tests
     * @return array
     */
    public function moduleSheetTestDataProvider()
    {
        $industry = $this->generateIndustry();
        $state = $this->generateState();
        $module = $this->generateModule();

        $studyQuestions = [];
        $answer = $this->generateAnswer();
        $question = $this->generateQuestion();
        $question->addAnswer($answer);
        $studyQuestions[] = $question;

        $answer = $this->generateAnswer(false, 'no bueno');
        $question = $this->generateQuestion(null, 'porque!!!!');
        $question->addAnswer($answer);
        $studyQuestions[] = $question;

        $practiceQuestionsA = [];
        $question = $this->generateQuestion();
        $answer1 = $this->generateAnswer(true, 'answer one');
        $answer2 = $this->generateAnswer(false, 'answer two');
        $answer3 = $this->generateAnswer(false, 'answer three');
        $answer4 = $this->generateAnswer(false, 'answer four');
        $question->addAnswer($answer1)
            ->addAnswer($answer2)
            ->addAnswer($answer3)
            ->addAnswer($answer4);
        $practiceQuestionsA[] = $question;

        $practiceQuestionsB = [];
        $question2 = $this->generateQuestion();
        $answer1b = $this->generateAnswer(false, 'answer one');
        $answer5 = $this->generateAnswer(false, 'answer five');
        $answer6 = $this->generateAnswer(true, 'answer six');
        $question2->addAnswer($answer1b)
            ->addAnswer($answer2)
            ->addAnswer($answer3)
            ->addAnswer($answer4)
            ->addAnswer($answer5)
            ->addAnswer($answer6);
        $practiceQuestionsB[] = $question2;

        $examQuestions = [];
        $question = $this->generateQuestion(null, 'first exam question');
        $answer1 = $this->generateAnswer(false, 'exam answer one');
        $answer2 = $this->generateAnswer(false, 'exam answer two');
        $answer3 = $this->generateAnswer(true, 'exam answer three');
        $answer4 = $this->generateAnswer(false, 'exam answer four');
        $question->addAnswer($answer1)
            ->addAnswer($answer2)
            ->addAnswer($answer3)
            ->addAnswer($answer4);
        $examQuestions[] = $question;

        $question = $this->generateQuestion(null, 'second exam question');
        $answer1 = $this->generateAnswer(false, 'exam answer one');
        $answer2 = $this->generateAnswer(false, 'exam answer two');
        $answer3 = $this->generateAnswer(true, 'exam answer three');
        $answer4 = $this->generateAnswer(false, 'exam answer four');
        $question->addAnswer($answer1)
            ->addAnswer($answer2)
            ->addAnswer($answer3)
            ->addAnswer($answer4);
        $question->setActive(false);
        $examQuestions[] = $question;

        $question = $this->generateQuestion(null, 'thirdexam question');
        $answer1 = $this->generateAnswer(false, 'exam answer one');
        $answer2 = $this->generateAnswer(false, 'exam answer two');
        $answer3 = $this->generateAnswer(false, 'exam answer three');
        $answer4 = $this->generateAnswer(true, 'exam answer four');
        $question->addAnswer($answer1)
            ->addAnswer($answer2)
            ->addAnswer($answer3)
            ->addAnswer($answer4);
        $examQuestions[] = $question;

        return [
            'no qs w industry and stats' => [$module, [], [], [], $industry, $state],
            'no qs w industry' => [$module, [], [], [], $industry, null],
            'no qs w state' => [$module, [], [], [], null, $state],
            'no qs no industry or state' => [$module, [], [], [], null, null],
            'study questions' => [$module, $studyQuestions, [], [], null, null],
            'practice questions' => [$module, [], $practiceQuestionsA, [], null, null],
            'practice questions 6 answers' => [$module, [], $practiceQuestionsB, [], null, null],
            'exam questions' => [$module, [], [], $examQuestions, null, null],

        ];
    }
}
