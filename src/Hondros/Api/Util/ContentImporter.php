<?php

namespace Hondros\Api\Util;

use DoctrineTest\InstantiatorTestAsset\ExceptionAsset;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Api\Util\Excel\Response;
use Hondros\Api\Util\Excel\Validator;
use Hondros\Api\Util\Helper\StringUtil;
use Hondros\Api\MessageQueue;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Laminas\Config\Config as LaminasConfig;
use InvalidArgumentException;
use Exception;
use DateTime;

class ContentImporter
{
    /**
     * Use String util helper
     */
    use StringUtil {
        getValueFromExcelCell as protected;
        getExcelAnswerChoiceIndex as protected;
        excelRowToVariables as protected;
    }

    /**
     * @var int number of days from enrollment the student has access to the exam
     */
    const EXAM_ACCESS_DAYS = 180;

    /**
     * @var string extension type for validation
     */
    const IMPORT_FILES_EXTENSION = 'xlsx';

    /**
     * @var int use this to remove questions from a question bank
     */
    const REMOVED_QUESTION_BANK_ID = 0;
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;
    
    /**
     * @var \Monolog\Logger
     */
    protected $logger;
    
    /**
     * @var \Hondros\Api\Model\Repository\Module
     */
    protected $moduleRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\Industry
     */
    protected $industryRepository;
    
    /**
     * @var \Laminas\Config\Config
     */
    protected $config;
    
    /**
     * @var \Hondros\Api\Model\Repository\Exam
     */
    protected $examRepository;
    
    /**
     * @var \Hondros\Api\Model\Repository\State
     */
    protected $stateRepository;
    
    /**
     * @var \Hondros\Api\MessageQueue\Question
     */
    protected $questionMessageQueue;

    /**
     * @var \Hondros\Api\Model\Repository\Question
     */
    protected $questionRepository;

    /**
     * @var Validator
     */
    protected $excelValidator;
    
    public function __construct(EntityManager $entityManager, Logger $logger, Repository\Module $moduleRepository,
        Repository\Industry $industryRepository, LaminasConfig $config, Repository\Exam $examRepository, 
        Repository\State $stateRepository, MessageQueue\Question $questionMessageQueue,
                                Repository\Question $questionRepository, Validator $excelValidator)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->moduleRepository = $moduleRepository;
        $this->industryRepository = $industryRepository;
        $this->config = $config;
        $this->examRepository = $examRepository;
        $this->stateRepository = $stateRepository;
        $this->questionMessageQueue = $questionMessageQueue;
        $this->questionRepository = $questionRepository;
        $this->excelValidator = $excelValidator;
    }

    /**
     * Can be triggered by a console job or by an endpoint
     *
     * @param string $type
     * @param array|null $files
     * @return array
     */
    public function importFiles($type, $files = null)
    {
        $items = [];
        $errors = [];
        $method = null;
        $validateMethod = null;
        
        // what are we importing
        switch (strtolower($type)) {
            case 'exams':
                $method = 'importExam';
                $validateMethod = 'validateExamFile';
                $itemsPath = realpath(getcwd() . $this->config->import->examPath);
                break;
            case 'modules':
                $method = 'importModule';
                $validateMethod = 'validateModuleFile';
                $itemsPath = realpath(getcwd() . $this->config->import->modulePath);
                break;
            default:
                throw new \InvalidArgumentException("Invalid import type {$type} specified.");
                break;
        }

        if (!is_null($files)) {
            foreach ($files as $file) {
                /** @var Response $response */
                $response = $this->excelValidator->{$validateMethod}($file['tmp_name']);

                if (!$response->isValid()) {
                    $errors[$file['name']] = $response->getErrors();
                    continue;
                }

                try {
                    $items[] = $this->{$method}($file['tmp_name']);
                } catch (Exception $e) {
                    $errors[] = "Error with file {$file['name']} " . $e->getMessage();
                }
            }
        } else {
            $dir = dir($itemsPath);
            while (false !== ($file = $dir->read())) {
                // ignore certain items
                if (substr($file, -4) !== self::IMPORT_FILES_EXTENSION) {
                    continue;
                }

                /** @var Response $response */
                $response = $this->excelValidator->{$validateMethod}($itemsPath . DIRECTORY_SEPARATOR . $file);

                if (!$response->isValid()) {
                    $errors[$file] = $response->getErrors();
                    continue;
                }

                try {
                    $items[] = $this->{$method}($itemsPath . DIRECTORY_SEPARATOR . $file);
                    @unlink($itemsPath . DIRECTORY_SEPARATOR . $file);
                } catch (Exception $e) {
                    $errors[] = "Error with file {$file} " . $e->getMessage();
                }
            }
            $dir->close();
        }

        return [
            'success' => true,
            'itemsAdded' => count($items),
            'errors' => $errors
        ];
    }

    public function updateModules()
    {
        $items = [];
        $errors = [];
    
        $itemsPath = realpath(getcwd() . $this->config->import->modulePath . DIRECTORY_SEPARATOR . 'updates');

        // make sure the path is there
        if (empty($itemsPath)) {
            throw new \Exception("Path to module update files not found in "
                . getcwd() . $this->config->import->modulePath . DIRECTORY_SEPARATOR . 'updates');
        }
        
        $dir = dir($itemsPath);
        while (false !== ($file = $dir->read())) {
            // ignore certain items
            if (substr($file, -4) !== self::IMPORT_FILES_EXTENSION) {
                continue;
            }

            try {
                $items[] = $this->updateModule($itemsPath . DIRECTORY_SEPARATOR . $file);
            } catch (Exception $e) {
                if ($e->getCode() != 409) {
                    $errors[] = "Error with file {$file} " . $e->getMessage();
                }
            }
            
            // move to done
            $doneDir = $itemsPath . DIRECTORY_SEPARATOR . 'done';
            if (false === realpath($doneDir)) {
                mkdir($doneDir, 0775, true);
            }
            
            rename($itemsPath . DIRECTORY_SEPARATOR . $file, $doneDir . DIRECTORY_SEPARATOR . $file);
        }
    
        $dir->close();
    
        return [
            'success' => true,
            'itemsAdded' => count($items),
            'errors' => $errors
        ];
    }

    /**
     * @todo we'll need to check for duplicate questions before adding new ones moving forward
     * @param string $file
     * @return bool
     * @throws Exception
     */
    public function importModule($file)
    {
        // make sure it's a valid file
        try {
            $excel = IOFactory::load($file);
        } catch (Exception $e) {
            throw new Exception("Unable to load excel module file {$file} due to {$e->getMessage()}");
        }

        $date = new DateTime();

        // sheets
        try {
            $moduleSheet = $excel->getSheet(0);
            $studySheet = $excel->getSheet(1);
            $practiceSheet = $excel->getSheet(2);
            $examSheet = $excel->getSheet(3);
        } catch (Exception $e) {
            throw new Exception("Unable to load all sheets for file {$file} due to {$e->getMessage()}");
        }
        
        // module sheet vars
        $moduleCode = trim($moduleSheet->getCell('B1')->getValue());
        $moduleName = trim($moduleSheet->getCell('B2')->getValue());
        $industryName = trim($moduleSheet->getCell('B3')->getValue());
        $stateAbbr = trim($moduleSheet->getCell('B4')->getValue());
        
        // make sure module doesn't already exist
        $module = $this->moduleRepository->findOneByCode($moduleCode);
        
        if (!empty($module)) {
            throw new InvalidArgumentException("Module for {$moduleCode} already exists.", 409);
        }
        
        // what industry
        $industry = $this->industryRepository->findOneByName($industryName);
        
        if (empty($industry)) {
            throw new InvalidArgumentException("Unable to find industry {$industryName}.");    
        }

        // what state
        $state = null;
        if (!empty($stateAbbr)) {
            $state = $this->stateRepository->findOneByCode($stateAbbr);
            
            // can also try name if the excel doesn't have the code
            if (empty($state)) {
                $state = $this->stateRepository->findOneByName($stateAbbr);
            }
            
            // validate
            if (empty($state)) {
                throw new InvalidArgumentException("Invalid state {$stateAbbr}");
            }
        }
        
        // create module
        $module = new Entity\Module();
        $module->setCode($moduleCode)
            ->setName($moduleName)
            ->setIndustry($industry)
            ->setState($state)
            ->setCreated($date)
            ->setStatus($module::STATUS_IMPORTING);
        
        $this->entityManager->persist($module);
        
        // store the module so another job doesn't accidentally start working on this one as well
        $this->entityManager->flush();
        $moduleId = $module->getId();

        // loop for practice and exam
        $bankTypes = [
            Entity\ModuleQuestion::TYPE_STUDY,
            Entity\ModuleQuestion::TYPE_PRACTICE,
            Entity\ModuleQuestion::TYPE_EXAM
        ];

        // @todo we can remove all question bank stuff now?
        foreach ($bankTypes as $bankType) {
            $bank = new Entity\QuestionBank();
            $bank->setType($bankType);
            $this->entityManager->persist($bank);

            /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet */
            $sheet = ${$bankType . 'Sheet'};
            $questionsCount = 0;
            for ($x = 2; $x <= $sheet->getHighestRow(); $x++) {

                list($questionText, $answerTexts, $feedback, $correctAnswer, $answerColumns) = $this->excelRowToVariables($sheet, $x);

                // make sure there is something there
                if (empty($questionText)) {
                    break;
                }

                // check if this questions is a dup before inserting it
                $question = $this->questionRepository->findDuplicationQuestion($questionText, $answerTexts, $feedback);

                if (is_null($question)) {
                    try {
                        if ($bankType == 'study') {
                            $question = $this->addStudyQuestion(
                                $bank,
                                $questionText,
                                $answerTexts[0]);
                        } else {
                            $question = $this->{'add' . ucwords($bankType) . 'Question'}(
                                $bank,
                                $questionText,
                                $answerTexts,
                                $correctAnswer,
                                $feedback);
                        }
                    } catch (InvalidArgumentException $e) {
                        throw new InvalidArgumentException("{$bankType} question A{$x} is invalid: {$e->getMessage()}");
                    } catch (Exception $e) {
                        throw new Exception("Unable to add new {$bankType} question A{$x} due to {$e->getMessage()}");
                    }
                }

                $questionsCount += 1;

                // all good, add module question
                $moduleQuestion = (new Entity\ModuleQuestion())
                    ->setModule($this->entityManager->getReference('Hondros\Api\Model\Entity\Module', $module->getId()))
                    ->setQuestion($this->entityManager->getReference('Hondros\Api\Model\Entity\Question', $question->getId()))
                    ->setType($bankType);

                $this->entityManager->persist($moduleQuestion);

                // @todo remove after new schema
                $bank->setQuestionCount($questionsCount);
                $module->{'set' . ucwords($bankType) . 'Bank'}($bank);

                // business decision to use the same questions in exam in preassessments
                if ($bankType == 'exam') {
                    $moduleQuestion = (new Entity\ModuleQuestion())
                        ->setModule($this->entityManager->getReference('Hondros\Api\Model\Entity\Module', $module->getId()))
                        ->setQuestion($this->entityManager->getReference('Hondros\Api\Model\Entity\Question', $question->getId()))
                        ->setType(Entity\ModuleQuestion::TYPE_PREASSESSMENT);

                    $this->entityManager->persist($moduleQuestion);

                    // @todo remove after new schema code
                    $module->setPreassessmentBank($bank);
                }
            }

            // clean up
            $this->entityManager->flush();
            $this->entityManager->clear();
            $module = $this->moduleRepository->find($moduleId);
        }

        // update module status
        $module->setStatus($module::STATUS_ACTIVE);

        // save all
        $this->entityManager->flush();

        // clear up uof
        $this->entityManager->getUnitOfWork()->clear();

        // clean up
        unset($excel);
        unset($module);

        return true;
    }
    
    /**
     * update an existing module
     *
     * There are three action items taken depending on the color of the question text
     * green: add a new question with answers
     * red: remove the current question from the question bank
     * blue: the question needs updating on all the cells highlighted in yellow
     * 
     * @param string $file
     * @throws Exception
     * @return boolean
     */
    public function updateModule($file)
    {
        // make sure it's a valid file
        try {
            $excel = IOFactory::load($file);
        } catch (Exception $e) {
            throw new Exception("Unable to load excel update module file {$file} due to {$e->getMessage()}");
        }

        $date = new DateTime();

        // sheets
        try {
            $moduleSheet = $excel->getSheet(0);
            $studySheet = $excel->getSheet(1);
            $practiceSheet = $excel->getSheet(2);
            $examSheet = $excel->getSheet(3);
        } catch (Exception $e) {
            throw new Exception("Unable to load all sheets for file {$file} due to {$e->getMessage()}");
        }

        // module sheet vars
        $moduleCode = trim($moduleSheet->getCell('B1')->getValue());
    
        // make sure the module is there
        /** @var Entity\Module $module */
        $module = $this->moduleRepository->findOneByCode($moduleCode);
    
        if (empty($module)) {
            throw new InvalidArgumentException("Module {$moduleCode} was not found", 404);
        }

        // loop for study, practice, and exam
        $bankTypes = [
            Entity\ModuleQuestion::TYPE_STUDY,
            Entity\ModuleQuestion::TYPE_PRACTICE,
            Entity\ModuleQuestion::TYPE_EXAM
        ];
        foreach ($bankTypes as $bankType) {
            /** @var Entity\QuestionBank $bank */
            // @todo remove all references to question bank after new schema goes live
            $bank = $module->{'get' . ucwords($bankType) . 'Bank'}();

            // get all questions for later
            /** @var Entity\Question[] $remainingBankQuestions */
            $remainingBankQuestions = $this->questionRepository->findForModule($module->getId(), $bankType);

            // loop through questions in sheet
            /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet */
            $sheet = ${$bankType . 'Sheet'};
            for ($x = 2; $x <= $sheet->getHighestRow(); $x++) {

                list($questionText, $answerTexts, $feedback, $correctAnswer, $answerColumns) = $this->excelRowToVariables($sheet, $x);

                // make sure there is something there
                if (empty($questionText)) {
                    break;
                }

                $matchingQuestion = $this->findMatchingQuestion(
                    $remainingBankQuestions,
                    $sheet,
                    $x,
                    $answerColumns
                );

                // what needs to be done?
                $color = $sheet->getStyle("A{$x}")->getFont()->getColor()->getRGB();
                $red = hexdec(substr($color, 0, 2));
                $green = hexdec(substr($color, 2, 2));
                $blue = hexdec(substr($color, 4, 2));

                // if black text we are done
                if ($red == $green && $green == $blue) {
                    continue;

                } else if ($green > $red && $green > $blue && empty($matchingQuestion)) { // add
                    $method = 'add' . ucwords($bankType) . 'Question';

                    // check if this questions is a dup before inserting it
                    $newQuestion = $this->questionRepository->findDuplicationQuestion($questionText, $answerTexts, $feedback);

                    if (is_null($newQuestion)) {
                        try {
                            if ($bankType == 'study') {
                                $newQuestion = $this->$method(
                                    $bank,
                                    $questionText,
                                    $answerTexts[0]);
                            } else {
                                $newQuestion = $this->$method(
                                    $bank,
                                    $questionText,
                                    $answerTexts,
                                    $correctAnswer,
                                    $feedback);
                            }
                        } catch (InvalidArgumentException $e) {
                            throw new InvalidArgumentException("{$bankType} question A{$x} is invalid: {$e->getMessage()}");
                        } catch (Exception $e) {
                            throw new Exception("Unable to add new {$bankType} question A{$x} due to {$e->getMessage()}");
                        }
                    }

                    $moduleQuestion = (new Entity\ModuleQuestion())
                        ->setQuestion($this->entityManager
                            ->getReference('Hondros\Api\Model\Entity\Question', $newQuestion->getId()))
                        ->setModule($this->entityManager
                            ->getReference('Hondros\Api\Model\Entity\Module', $module->getId()))
                        ->setType($bankType);

                    $this->entityManager->persist($moduleQuestion);

                } else if ($red > $green && $red > $blue && !empty($matchingQuestion)) { // disable
                    $matchingQuestion->setActive(false);
                    $matchingQuestion->setModified($date);

                } else if ($blue > $red && $blue > $green && !empty($matchingQuestion)) { // update
                    $matchingQuestion->setQuestionText($questionText);
                    $matchingQuestion->setFeedback(empty($feedback) ? null : $feedback);
                    $matchingQuestion->setModified($date);

                    // update answers
                    $answers = $matchingQuestion->getAnswers();
                    for ($y = 0; $y < count($answers); $y++) {
                        $answers[$y]->setAnswerText($answerTexts[$y]);
                        $answers[$y]->setCorrect($y == $this->getExcelAnswerChoiceIndex($correctAnswer));
                        $answers[$y]->setModified($date);
                    }

                } else {
                    throw new \Exception("Invalid color or question found at A{$x}.");
                }
            }
        }

        // save all
        $this->entityManager->flush();
    
        // clean up
        unset($excel);
        unset($module);
    
        // clear up uof
        $this->entityManager->getUnitOfWork()->clear();
    
        return true;
    }

    /**
     * Try to find a matching question
     *
     * @param Entity\Question[] $remainingBankQuestions
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $index
     * @param int $answerColumns number of answer columns in sheet
     * @return Entity\Question
     */
    protected function findMatchingQuestion(&$remainingBankQuestions, $sheet, $index, $answerColumns)
    {
        $questionCell = $sheet->getCell("A{$index}");
        $questionBackgroundColor = $sheet->getStyle("A{$index}")->getFill()->getStartColor()->getRGB();
        $questionText = $this->getValueFromExcelCell($questionCell);

        $answerCell1 = $sheet->getCell("B{$index}");
        $answerCell1BackgroundColor = $sheet->getStyle("B{$index}")->getFill()->getStartColor()->getRGB();
        $answer1Text = $this->getValueFromExcelCell($answerCell1);

        $answerCell2 = $sheet->getCell("C{$index}");
        $answer2Text = $this->getValueFromExcelCell($answerCell2);

        // dynamically set columns
        $feedbackColumn = chr(ord('B') + $answerColumns);

        $feedbackCell = $sheet->getCell("{$feedbackColumn}{$index}");
        $feedbackBackgroundColor = $sheet->getStyle("{$feedbackColumn}{$index}")->getFill()->getStartColor()->getRGB();
        $feedbackText = $this->getValueFromExcelCell($feedbackCell);

        $foundQuestion = false;
        for ($x = 0, $max = count($remainingBankQuestions); $x < $max; $x++) {
            $remainingBankQuestion = $remainingBankQuestions[$x];

            // trust question text first as long as it's not with yellow bg
            if ($questionBackgroundColor != 'FFFF00' && $questionText == $remainingBankQuestion->getQuestionText()) {
                $foundQuestion = $remainingBankQuestions[$x];
                break;
            }

            if ($questionBackgroundColor == 'FFFF00' && !empty($feedbackText) && $feedbackBackgroundColor != 'FFFF00'
                && $feedbackText == $remainingBankQuestion->getFeedback()) {
                $foundQuestion = $remainingBankQuestions[$x];
                break;
            }

            // for vocab
            if ($questionBackgroundColor == 'FFFF00' && $answerCell1BackgroundColor != 'FFFF00'
                && empty($answer2Text) && $answer1Text == $remainingBankQuestion->getAnswers()[0]->getAnswerText()) {
                $foundQuestion = $remainingBankQuestions[$x];
                break;
            }

            // do we have a close match?
            similar_text($questionText, $remainingBankQuestion->getQuestionText(), $questionTextSimilarity);
            similar_text($feedbackText, $remainingBankQuestion->getFeedback(), $feedbackTextSimilarity);
            similar_text($answer1Text, $remainingBankQuestion->getAnswers()[0]->getAnswerText(), $answer1TextSimilarity);

            $match = 0;
            if (!empty($feedbackText)) {
                $match = ($questionTextSimilarity + $feedbackTextSimilarity) / 2;
            } else if (empty($answer2Text) && empty($feedbackText)) {
                $match = ($questionTextSimilarity + $answer1TextSimilarity) / 2;
            } else if (!empty($answer2Text) && empty($feedbackText)) {
                $match = $questionTextSimilarity;
            }

            if ($match >= 95) {
                $foundQuestion = $remainingBankQuestions[$x];
                break;
            }
        }

        if ($foundQuestion) {
            unset($remainingBankQuestions[$x]);
            // reindex
            $remainingBankQuestions = array_values($remainingBankQuestions);
        }

        return $foundQuestion;
    }

    /**
     * @param string $file
     * @return bool
     * @throws Exception
     */
    public function importExam($file)
    {
        try {
            $excel = IOFactory::load($file);
        } catch (\Exception $e) {
            throw new Exception("Unable to load excel exam file {$file} due to {$e->getMessage()}");
        }
        $date = new DateTime();
        
        // sheets
        $examSheet = $excel->getSheet(0);
        
        // vars
        $examCode = trim($examSheet->getCell('B1')->getValue());
        $examName = trim($examSheet->getCell('B2')->getValue());
        $examDescription = trim($examSheet->getCell('B3')->getValue());
        $industryName = trim($examSheet->getCell('B4')->getValue());
        $stateAbbr = trim($examSheet->getCell('B5')->getValue());
        $examTime = trim($examSheet->getCell('B6')->getValue());
        $accessTime = trim($examSheet->getCell('B7')->getValue());
        
        // make sure exam doesn't already exist
        $exam = $this->examRepository->findOneByCode($examCode);
        
        if (!empty($exam)) {
            throw new InvalidArgumentException("Exam for {$examCode} already exists.", 409);
        }
        
        // what industry
        $industry = $this->industryRepository->findOneByName($industryName);
        
        if (empty($industry)) {
            throw new InvalidArgumentException("Unable to find industry {$industryName}.");
        }
        
        // what state
        $state = null;
        if (!empty($stateAbbr)) {
            $state = $this->stateRepository->findOneByCode($stateAbbr);
        
            // validate
            if (empty($state)) {
                throw new InvalidArgumentException("Invalid state {$stateAbbr}.");
            }
        }
        
        // convert to seconds
        if ($examTime != '') {
            $examTime = (int) $examTime * 60;
        }
        
        // do we have an access value?
        if ($accessTime == '') {
            $accessTime = self::EXAM_ACCESS_DAYS;
        }
        
        // state module
        $exam = (new Entity\Exam())
            ->setCode($examCode)
            ->setName($examName)
            ->setDescription($examDescription)
            ->setIndustry($industry)
            ->setState($state)
            ->setExamTime($examTime)
            ->setAccessTime($accessTime)
            ->setCreated($date);
        $this->entityManager->persist($exam);
        
        // now loop through all modules and make sure they are in the system and add exam module
        // now add study questions
        $examModuleIndex = 1;
        for ($x = 9; $x <= $examSheet->getHighestRow(); $x++) {
            // if nothing then we are done
            if (empty(trim($examSheet->getCell("A{$x}")->getValue()))) {
                break;
            }
            
            // find module
            $moduleCode = trim($examSheet->getCell("A{$x}")->getValue());

            /** @var Entity\Module[] $modules */
            $modules = $this->moduleRepository->findByCode($moduleCode); 
            
            // vars
            $moduleNameOverride = trim($examSheet->getCell("B{$x}")->getValue());
            $preassessmentQuestions = trim($examSheet->getCell("E{$x}")->getValue());
            $practiceQuestions = trim($examSheet->getCell("C{$x}")->getValue());
            $examQuestions = trim($examSheet->getCell("D{$x}")->getValue());
            
            if (empty($modules)) {
                throw new \DomainException("Unable to find required module {$moduleCode} for this exam.");
            }
            
            // make sure module is ready to be used
            if ($modules[0]->getStatus() != $modules[0]::STATUS_ACTIVE) {
                throw new \DomainException("Trying to use a module {$moduleCode} that is not active.");
            }
            
            // lets create the exam module now
            $examModule = (new Entity\ExamModule())
                ->setExam($exam)
                ->setModule($modules[0])
                ->setName($moduleNameOverride)
                ->setPreassessmentQuestions($preassessmentQuestions)
                ->setPracticeQuestions($practiceQuestions)
                ->setExamQuestions($examQuestions)
                ->setSort($examModuleIndex)
                ->setCreated($date);
            $this->entityManager->persist($examModule);
            
            // increment index
            $examModuleIndex++;
        }
        
        // all good, lets save it all
        $this->entityManager->flush();
        
        // clean up
        unset($excel);

        return true;
    }

    /**
     * @param Entity\QuestionBank $bank
     * @param string $questionText
     * @param string $answerText
     * @return Entity\Question
     */
    protected function addStudyQuestion(Entity\QuestionBank $bank, $questionText, $answerText)
    {
        return $this->questionRepository->createNew(
            'vocab',
            $bank,
            $questionText,
            [$answerText],
            $this->getExcelAnswerChoiceIndex('A')
        );
    }

    /**
     * @param Entity\QuestionBank $bank
     * @param string $questionText
     * @param array $answerTexts
     * @param int $correctAnswer
     * @param string|null $feedback
     * @return Entity\Question
     */
    protected function addPracticeQuestion(Entity\QuestionBank $bank, $questionText, $answerTexts, $correctAnswer,
                                           $feedback = null)
    {
        return $this->questionRepository->createNew(
            'multi',
            $bank,
            $questionText,
            $answerTexts,
            $this->getExcelAnswerChoiceIndex($correctAnswer),
            $feedback
        );
    }

    /**
     * @param Entity\QuestionBank $bank
     * @param string $questionText
     * @param array $answerTexts
     * @param int $correctAnswer
     * @param string|null $feedback
     * @return Entity\Question
     */
    protected function addExamQuestion(Entity\QuestionBank $bank, $questionText, $answerTexts, $correctAnswer,
                                       $feedback = null)
    {
        return $this->questionRepository->createNew(
            'multi',
            $bank,
            $questionText,
            $answerTexts,
            $this->getExcelAnswerChoiceIndex($correctAnswer),
            $feedback
        );
    }
}