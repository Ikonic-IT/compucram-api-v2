<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 4/11/17
 * Time: 2:26 PM
 */

namespace Hondros\Api\Util\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Hondros\Api\Model\Repository;
use Hondros\Api\Util\Helper\StringUtil;

/**
 * Class Validator
 * @package Hondros\Api\Util\Excel
 */
class Validator
{
    /**
     * Use String util helper
     */
    use StringUtil {
        getValueFromExcelCell as protected;
        containInvalidMySQLChar as protected;
        getExcelAnswerChoiceIndex as protected;
        excelRowToVariables as protected;
    }

    /**
     * @var Repository\Industry
     */
    protected $industryRepository;

    /**
     * @var Repository\State
     */
    protected $stateRepository;

    /**
     * @var Repository\Module
     */
    protected $moduleRepository;

    /**
     * Validator constructor.
     * @param Repository\Industry $industryRepository
     * @param Repository\State $stateRepository
     * @param Repository\Module $moduleRepository
     */
    public function __construct(Repository\Industry $industryRepository, Repository\State $stateRepository,
        Repository\Module $moduleRepository)
    {
        $this->industryRepository = $industryRepository;
        $this->stateRepository = $stateRepository;
        $this->moduleRepository = $moduleRepository;
    }

    /**
     * @param string $filePath
     * @return Response
     */
    public function validateExamFile($filePath)
    {
        $response = new Response();
        $response->setFilePath($filePath);

        try {
            $excel = IOFactory::load($filePath);
        } catch (\Exception $e) {
            return $response->addError("File not found.");
        }

        /** @var Worksheet $sheet */
        $sheet = $excel->getSheet(0);

        $code = $sheet->getCell('B1')->getValue();
        $name = $sheet->getCell('B2')->getValue();
        $description = $sheet->getCell('B3')->getValue();
        $industryName = $sheet->getCell('B4')->getValue();
        $stateCode = $sheet->getCell('B5')->getValue();
        $examTime = $sheet->getCell('B6')->getValue();

        // exam properties
        if (empty($code)) {
            $response->addError("Invalid id at B1.");
        }

        if (empty($name)) {
            $response->addError("Invalid name at B2.");
        }

        if (empty($description)) {
            $response->addError("Invalid description at B3.");
        }

        if (empty($industryName)) {
            $response->addError("Invalid industry at B4.");
        }

        if (empty($examTime) || false === filter_var($examTime, FILTER_VALIDATE_INT)) {
            $response->addError("Invalid time in seconds at B6.");
        }

        if (!empty($industryName)) {
            $industry = $this->industryRepository->findOneByName($industryName);
            if (empty($industry)) {
                $response->addError("Industry not found at B4.");
            }
        }

        if (!empty($stateCode)) {
            $state = $this->stateRepository->findOneByCode($stateCode);
            if (empty($state)) {
                $response->addError("State not found at B5.");
            }
        }

        // exam modules
        $currentIndex = 9;
        while (!empty($sheet->getCell("A{$currentIndex}")->getValue())) {
            $moduleCode = $sheet->getCell("A{$currentIndex}")->getValue();
            $moduleName = $sheet->getCell("B{$currentIndex}")->getValue();
            $modulePracticeQuestions = $sheet->getCell("C{$currentIndex}")->getValue();
            $moduleExamQuestions = $sheet->getCell("D{$currentIndex}")->getValue();
            $modulePreassessmentQuestions = $sheet->getCell("E{$currentIndex}")->getValue();

            $module = $this->moduleRepository->findOneByCode($moduleCode);
            if (empty($module)) {
                $response->addError("Module not found for code {$moduleCode} at A{$currentIndex}.");
            }

            if (empty($moduleName)) {
                $response->addError("Invalid module name at B{$currentIndex}.");
            }

            if (false === filter_var($modulePracticeQuestions, FILTER_VALIDATE_INT)) {
                $response->addError("Invalid practice question count at C{$currentIndex}.");
            }

            if (false === filter_var($moduleExamQuestions, FILTER_VALIDATE_INT)) {
                $response->addError("Invalid exam question count at D{$currentIndex}.");
            }

            if (false === filter_var($modulePreassessmentQuestions, FILTER_VALIDATE_INT)) {
                $response->addError("Invalid preassessment question count at E{$currentIndex}.");
            }

            $currentIndex++;
        }

        if ($currentIndex === 9) {
            $response->addError("No modules found.");
        }

        return $response;
    }

    /**
     * @param string $filePath
     * @return Response
     */
    public function validateModuleFile($filePath)
    {
        $response = new Response();
        $response->setFilePath($filePath);

        try {
            $excel = IOFactory::load($filePath);
        } catch (\Exception $e) {
            return $response->addError("File not found.");
        }

        if ($excel->getSheetCount() !== 4) {
            return $response->addError("This file should contain 4 sheets, only {$excel->getSheetCount()} found.");
        }

        /** @var Worksheet $infoSheet */
        $infoSheet = $excel->getSheet(0);

        /** @var Worksheet $studySheet */
        $studySheet = $excel->getSheet(1);

        /** @var Worksheet $practiceSheet */
        $practiceSheet = $excel->getSheet(2);

        /** @var Worksheet $examSheet */
        $examSheet = $excel->getSheet(3);

        $code = $infoSheet->getCell('B1')->getValue();
        $name = $infoSheet->getCell('B2')->getValue();
        $industryName = $infoSheet->getCell('B3')->getValue();
        $stateCode = $infoSheet->getCell('B4')->getValue();

        // exam properties
        if (empty($code)) {
            $response->addError("Invalid id at B1 in {$infoSheet->getTitle()} sheet.");
        }

        if (empty($name)) {
            $response->addError("Invalid name at B2 in {$infoSheet->getTitle()} sheet.");
        }

        if (empty($industryName)) {
            $response->addError("Invalid industry at B3 in {$infoSheet->getTitle()} sheet.");
        }

        if (!empty($industryName)) {
            $industry = $this->industryRepository->findOneByName($industryName);
            if (empty($industry)) {
                $response->addError("Industry not found at B3 in {$infoSheet->getTitle()} sheet.");
            }
        }

        if (!empty($stateCode)) {
            $state = $this->stateRepository->findOneByCode($stateCode);
            if (empty($state)) {
                $response->addError("State not found at B4 in {$infoSheet->getTitle()} sheet.");
            }
        }

        $this->validateVocabSheet($studySheet, $response);
        $this->validateMultiSheet($practiceSheet, $response);
        $this->validateMultiSheet($examSheet, $response);

        return $response;
    }

    /**
     * Make sure all questions have 1 answer
     *
     * @param Worksheet $sheet
     * @param Response $response
     * @return Response
     */
    protected function validateVocabSheet(Worksheet $sheet, Response $response)
    {
        $questionHashes = [];
        for ($x = 2, $max = $sheet->getHighestRow(); $x <= $max; $x++) {
            $question = $this->getValueFromExcelCell($sheet->getCell("A{$x}"));
            $answer = $this->getValueFromExcelCell($sheet->getCell("B{$x}"));
            $hash = md5($question);

            // make sure there is something there
            if (empty($question) && empty($answer)) {
                break;
            }

            if (empty($question)) {
                $response->addError("Invalid question at A{$x} in {$sheet->getTitle()} sheet.");
            }

            if (empty($answer)) {
                $response->addError("Invalid answer at B{$x} in {$sheet->getTitle()} sheet.");
            }

            if (!in_array($hash, $questionHashes)) {
                $questionHashes[] = $hash;
            } else {
                $response->addError("Invalid duplicate question at row {$x} in {$sheet->getTitle()} sheet.");
            }

            /**
             * check special characters
             */
            if ($this->containInvalidMySQLChar($question)) {
                $response->addError("Special question character found at row A{$x} in {$sheet->getTitle()} sheet.");
            }

            if ($this->containInvalidMySQLChar($answer)) {
                $response->addError("Special answer character found at row B{$x} in {$sheet->getTitle()} sheet.");
            }
        }

        return $response;
    }

    /**
     * Make sure all questions have what they need
     *
     * Answer columns are dynamically set between 4 and 10
     *
     * @param Worksheet $sheet
     * @param Response $response
     * @return Response
     */
    protected function validateMultiSheet(Worksheet $sheet, Response $response)
    {
        $questionHashes = [];
        for ($x = 2, $max = $sheet->getHighestRow(); $x <= $max; $x++) {
            list($question, $answers, $feedback, $correctAnswer, $answerColumns) = $this->excelRowToVariables($sheet, $x);

            $answerColumnLetter = 'B';
            for ($y = 0; $y < $answerColumns; $y++) {
                $answer = isset($answers[$y]) ? $answers[$y] : null;
                $answerColumnLetter = chr(ord($answerColumnLetter) + 1);

                if (!empty($answer) && $this->containInvalidMySQLChar($answer)) {
                    $response->addError("Special answer character found at row {$answerColumnLetter}{$x} in {$sheet->getTitle()} sheet.");
                }
            }

            $feedbackColumn = chr(ord($answerColumnLetter) + 1);
            $correctAnswerColumn = chr(ord($answerColumnLetter) + 1);
            $correctAnswerIndex = $this->getExcelAnswerChoiceIndex($correctAnswer);
            $hash = md5(strtolower($question));

            // make sure there is something there
            if (empty($question) && empty($answers[0]) && empty($answers[1]) && empty($feedback) && empty($correctAnswer)) {
                break;
            }

            if (empty($question)) {
                $response->addError("Invalid question at A{$x} in {$sheet->getTitle()} sheet.");
            }

            if (empty($answers[0])) {
                $response->addError("Invalid answer at B{$x} in {$sheet->getTitle()} sheet.");
            }

            if (empty($answers[1])) {
                $response->addError("Invalid answer at C{$x} in {$sheet->getTitle()} sheet.");
            }

            if (empty($answers[2]) && !empty(trim($sheet->getCell("D{$x}")->getValue()))) {
                $response->addError("Invalid answer at D{$x} in {$sheet->getTitle()} sheet.");
            }

            /**
             * check special characters
             */
            if ($this->containInvalidMySQLChar($question)) {
                $response->addError("Special question character found at row A{$x} in {$sheet->getTitle()} sheet.");
            }

            if ($this->containInvalidMySQLChar($feedback)) {
                $response->addError("Special feedback character found at row {$feedbackColumn}{$x} in {$sheet->getTitle()} sheet.");
            }

            /**
             * this was used for multiple choice (true/false) to make answer 3 and 4 empty, bad hack don't allow
             */
            if (!empty($answers[2]) && $answers[2] === '*') {
                $response->addError("Invalid answer at D{$x} in {$sheet->getTitle()} sheet.");
            }

            if (empty($answers[3]) && !empty(trim($sheet->getCell("E{$x}")->getValue()))) {
                $response->addError("Invalid answer at E{$x} in {$sheet->getTitle()} sheet.");
            }

            /**
             * this was used for multiple choice (true/false) to make answer 3 and 4 empty, bad hack don't allow
             */
            if (!empty($answers[3]) && $answers[3] === '*') {
                $response->addError("Invalid answer at E{$x} in {$sheet->getTitle()} sheet.");
            }

            if ($correctAnswerIndex === false || empty($answers[$correctAnswerIndex])) {
                $response->addError("Invalid correct answer option at {$correctAnswerColumn}{$x} in {$sheet->getTitle()} sheet.");
            }

            if (!in_array($hash, $questionHashes)) {
                $questionHashes[] = $hash;
            } else {
                $response->addError("Invalid duplicate question at row {$x} in {$sheet->getTitle()} sheet.");
            }

        }

        return $response;
    }
}