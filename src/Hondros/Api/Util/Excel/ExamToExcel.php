<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/16/17
 * Time: 7:07 PM
 */

namespace Hondros\Api\Util\Excel;

use Hondros\Api\Model\Entity;
use Hondros\Api\Util\Helper\QuestionUtil;
use Hondros\Api\Util\Helper\StringUtil;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class ExamToExcel
{
    use QuestionUtil { getCorrectAnswerLetter as protected; }
    use StringUtil { incrementLetter as protected; }

    /**
     * @param \Hondros\Api\Model\Entity\Exam $exam
     * @param \Hondros\Api\Model\Entity\ExamModule[] $examModules
     * @return \Hondros\Api\Util\Excel\Response
     */
    public function createFileForExam(Entity\Exam $exam, $examModules)
    {
        $industry = $exam->getIndustry();
        $state = $exam->getState();

        $filePath = sys_get_temp_dir() . "/{$exam->getCode()}.xlsx";
        $excel = new Spreadsheet();
        $workSheet = $excel->setActiveSheetIndex(0)
            ->setTitle('Details')
            ->setCellValue('A1', $this->createHeadingText('Id'))
            ->setCellValue('A2', $this->createHeadingText('Title'))
            ->setCellValue('A3', $this->createHeadingText('Description'))
            ->setCellValue('A4', $this->createHeadingText('Industry'))
            ->setCellValue('A5', $this->createHeadingText('State Abbr'))
            ->setCellValue('A6', $this->createHeadingText('Exam Time'))
            ->setCellValue('A7', $this->createHeadingText('Access Time'))
            ->setCellValue('A8', $this->createHeadingText('Category Id'))
            ->setCellValue('B8', $this->createHeadingText('Category Name'))
            ->setCellValue('C8', $this->createHeadingText('Practice Select Count'))
            ->setCellValue('D8', $this->createHeadingText('Exam Select Count'))
            ->setCellValue('E8', $this->createHeadingText('Pre-Assessment Select Count'))

            ->setCellValue('B1', $exam->getCode())
            ->setCellValue('B2', $exam->getName())
            ->setCellValue('B3', $exam->getDescription())
            ->setCellValue('B4', !is_null($industry) ? $industry->getName() : null)
            ->setCellValue('B5', !is_null($state) ? $state->getCode() : null)
            ->setCellValue('B6', $exam->getExamTime() / 60) // show as minutes
            ->setCellValue('B7', $exam->getAccessTime()) // days

            ->setCellValue('C6', $this->createHeadingText('In minutes'))
            ->setCellValue('C7', $this->createHeadingText('In days'));

        // make sure exam modules are arranged correctly by sort
        usort($examModules, function ($a, $b) {
            if ($a->getSort() == $b->getSort()) {
                return 0;
            }

            return ($a->getSort() < $b->getSort()) ? -1 : 1;
        });

        $currentIndex = 9;
        foreach ($examModules as $examModule) {
            $workSheet->setCellValue("A{$currentIndex}", $examModule->getModule()->getCode());
            $workSheet->setCellValue("B{$currentIndex}", $examModule->getName());
            $workSheet->setCellValue("C{$currentIndex}", $examModule->getPracticeQuestions());
            $workSheet->setCellValue("D{$currentIndex}", $examModule->getExamQuestions());
            $workSheet->setCellValue("E{$currentIndex}", $examModule->getPreassessmentQuestions());
            $currentIndex++;
        }

        $excel->setActiveSheetIndex(0);
        $excel = IOFactory::createWriter($excel, 'Xlsx');
        $excel->save($filePath);

        $response = new Response();
        $response->setFilePath($filePath);

        return $response;
    }

    /**
     * @param \Hondros\Api\Model\Entity\Module $module
     * @param array $studyQuestions
     * @param array $practiceQuestions
     * @param array $examQuestions
     * @return \Hondros\Api\Util\Excel\Response
     */
    public function createFileForModule(Entity\Module $module, $studyQuestions, $practiceQuestions, $examQuestions)
    {
        $industry = $module->getIndustry();
        $state = $module->getState();

        $filePath = sys_get_temp_dir() . "/{$module->getCode()}.xlsx";
        $excel = new Spreadsheet();
        $workSheet = $excel->setActiveSheetIndex(0)->setTitle('Details')
            ->setCellValue('A1', $this->createHeadingText('Id'))
            ->setCellValue('A2', $this->createHeadingText('Title'))
            ->setCellValue('A3', $this->createHeadingText('Industry'))
            ->setCellValue('A4', $this->createHeadingText('State Abbr'))
            ->setCellValue('C1', 'unique code')
            ->setCellValue('C2', 'default name, can be overwritten when configured for the exam')
            ->setCellValue('C4', 'empty means it\'s available to be configured to any exam')

            ->setCellValue('B1', $module->getCode())
            ->setCellValue('B2', $module->getName())
            ->setCellValue('B3', !is_null($industry) ? $industry->getName() : null)
            ->setCellValue('B4', !is_null($state) ? $state->getCode() : null);

        $studySheet = $this->createVocabQuestionsSheet('StudyQuestions', $studyQuestions);
        $excel->addSheet($studySheet, 1);

        $practiceSheet = $this->createMultiQuestionsSheet('PracticeQuestions', $practiceQuestions);
        $excel->addSheet($practiceSheet, 2);

        $examSheet = $this->createMultiQuestionsSheet('ExamQuestions', $examQuestions);
        $excel->addSheet($examSheet, 3);

        $excel->setActiveSheetIndex(0);
        $excel = IOFactory::createWriter($excel, 'Xlsx');
        $excel->save($filePath);

        $response = new Response();
        $response->setFilePath($filePath);

        return $response;
    }

    /**
     * @param string $title
     * @param array $questions
     * @return Worksheet
     */
    protected function createVocabQuestionsSheet($title, $questions)
    {
        $workSheet = new Worksheet(null, $title);
        $workSheet->setCellValue('A1', $this->createHeadingText('Term'))
            ->setCellValue('B1', $this->createHeadingText('Definition'))
            ->setCellValue('C1', $this->createHeadingText('Active'));

        $currentIndex = 2;
        foreach ($questions as $question) {
            $answers = $question->getAnswers();

            if (empty($answers)) {
                // log something
                continue;
            }

            $workSheet->setCellValue("A{$currentIndex}", $question->getQuestionText())
                ->setCellValue("B{$currentIndex}", $answers[0]->getAnswerText())
                ->setCellValue("C{$currentIndex}", $question->getActive());
            $currentIndex++;
        }

        return $workSheet;
    }

    /**
     * @param string $title
     * @param array $questions
     * @return Worksheet
     */
    protected function createMultiQuestionsSheet($title, $questions)
    {
        $workSheet = new Worksheet(null, $title);
        $currentIndex = 2;
        $firstAnswerLetterIndex = 'B';

        // default to 4 columns for answers
        $lastAnswerLetterIndex = 'E';

        $workSheet->setCellValue('A1', $this->createHeadingText('Question'));

        foreach ($questions as $question) {
            $answers = $question->getAnswers();

            if (empty($answers)) {
                // log something
                continue;
            }

            $workSheet->setCellValue("A{$currentIndex}", $question->getQuestionText());

            $nextLetterIndex  = $firstAnswerLetterIndex;
            foreach ($answers as $answer) {
                if (!empty($answer)) {
                    $workSheet
                        ->setCellValue("{$nextLetterIndex}{$currentIndex}", $answer->getAnswerText());

                    if ($nextLetterIndex > $lastAnswerLetterIndex) {
                        $lastAnswerLetterIndex = $nextLetterIndex;
                    }

                    $nextLetterIndex = $this->incrementLetter($nextLetterIndex);
                }
            }

            $currentIndex++;
        }

        // how many answer columns
        $numberOfAnswerColumns = ord($lastAnswerLetterIndex) - ord($firstAnswerLetterIndex);

        // add all answer columns
        for ($x = 0; $x <= $numberOfAnswerColumns; $x++) {
            $workSheet->setCellValue("{$this->incrementLetter($firstAnswerLetterIndex, $x)}1",
                    $this->createHeadingText("Answer {$this->incrementLetter($firstAnswerLetterIndex, ($x-1))}"));
        }

        // loop again now that we know how many answers we have
        $currentIndex = 2;
        foreach ($questions as $question) {
            $answers = $question->getAnswers();

            if (empty($answers)) {
                // log something
                continue;
            }

            // need to loop back at the end so we can put these in the right place after we know what's the max answer columns
            $workSheet->setCellValue("{$this->incrementLetter($lastAnswerLetterIndex, 1)}{$currentIndex}", $question->getFeedback())
                ->setCellValue("{$this->incrementLetter($lastAnswerLetterIndex, 2)}{$currentIndex}", $this->getCorrectAnswerLetter($answers))
                ->setCellValue("{$this->incrementLetter($lastAnswerLetterIndex, 3)}{$currentIndex}", $question->getTechniques())
                ->setCellValue("{$this->incrementLetter($lastAnswerLetterIndex, 5)}{$currentIndex}", $question->getActive());

            $currentIndex++;
        }

        // add the rest of the headings
        $workSheet->setCellValue("{$this->incrementLetter($lastAnswerLetterIndex, 1)}1", $this->createHeadingText('Explanation'))
            ->setCellValue("{$this->incrementLetter($lastAnswerLetterIndex, 2)}1", $this->createHeadingText('Correct Answer'))
            ->setCellValue("{$this->incrementLetter($lastAnswerLetterIndex, 3)}1", $this->createHeadingText('Supporting Info'))
            ->setCellValue("{$this->incrementLetter($lastAnswerLetterIndex, 4)}1", $this->createHeadingText('Tips'))
            ->setCellValue("{$this->incrementLetter($lastAnswerLetterIndex, 5)}1", $this->createHeadingText('Active'));

        return $workSheet;
    }

    /**
     * automate the creating of formatted text objects
     * @param string $label
     * @return RichText
     */
    protected function createHeadingText($label)
    {
        $textObj = new RichText();
        $textOptions = $textObj->createTextRun($label);
        $textOptions->getFont()->setBold(true)->setSize(12);

        return $textObj;
    }
}