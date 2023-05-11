<?php
/**
 * Created by PhpStorm.
 * User: joey.rivera
 * Date: 3/16/17
 * Time: 6:13 PM
 */

namespace Hondros\Api\Service\Export;

use Aws\S3\Exception\S3Exception;
use Hondros\Api\Model\Entity;
use Hondros\Api\Model\Repository;
use Hondros\Api\Util\Excel\ExamToExcel;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use Monolog\Logger;
use Laminas\Config\Config;
use Aws\S3\S3Client;

class Content
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * @var \Hondros\Api\Model\Repository\Exam
     */
    protected $examRepository;

    /**
     * @var \Hondros\Api\Model\Repository\ExamModule
     */
    protected $examModuleRepository;

    /**
     * @var \Hondros\Api\Model\Repository\Question
     */
    protected $questionRepository;

    /**
     * @var ExamToExcel
     */
    protected $examToExcel;

    /**
     * @var \Laminas\Config\Config
     */
    protected $config;

    /**
     * @var \Aws\S3\S3Client
     */
    protected $s3Client;

    public function __construct(EntityManager $entityManager, Logger $logger, Repository\Exam $examRepository,
        Repository\ExamModule $examModuleRepository, ExamToExcel $examToExcel, Repository\Question $questionRepository,
        Config $config, S3Client $s3Client)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->examRepository = $examRepository;
        $this->examModuleRepository = $examModuleRepository;
        $this->examToExcel = $examToExcel;
        $this->questionRepository = $questionRepository;
        $this->config = $config;
        $this->s3Client = $s3Client;
    }

    /**
     * @param int $examId
     * @param array $options
     * @return array
     * @throws \InvalidArgumentException|\Exception
     */
    public function exportExam($examId, $options = [])
    {
        if (false === $examId = filter_var($examId, FILTER_VALIDATE_INT)) {
            throw new InvalidArgumentException("Invalid exam id {$examId}.", 400);
        }

        /** @var Entity\Exam $exam */
        $exam = $this->examRepository->find($examId);

        if (empty($exam)) {
            throw new InvalidArgumentException("Exam not found for id {$examId}.", 404);
        }

        $examModules = $this->examModuleRepository->findAllOverride(
            [['property' => 'exam', 'value' => $examId]],
            ['module'],
            [['property' => 'sort']]
        );

        $files = [];
        $response = $this->examToExcel->createFileForExam($exam, (array) $examModules->getIterator());

        if (!$response->isValid()) {
            throw new \Exception("Unable to create file for exam {$exam->getName()}.");
        }

        $files[] = $response->getFilePath();

        foreach ($examModules as $examModule) {
            /** @var Entity\Module $module */
            $module = $examModule->getModule();
            $studyQuestions = $this->questionRepository->findForModule(
                $module->getId(), Entity\ModuleQuestion::TYPE_STUDY, 500);
            $practiceQuestions = $this->questionRepository->findForModule(
                $module->getId(), Entity\ModuleQuestion::TYPE_PRACTICE, 500);
            $examQuestions = $this->questionRepository->findForModule(
                $module->getId(), Entity\ModuleQuestion::TYPE_EXAM, 500);

            // if flag for all modules get them and add all to zip
            $response = $this->examToExcel->createFileForModule(
                $module,
                $studyQuestions,
                $practiceQuestions,
                $examQuestions
            );

            if (!$response->isValid()) {
                throw new \Exception("Unable to create file for modules {$module->getName()}.");
            }

            $files[] = $response->getFilePath();

            unset($studyQuestions);
            unset($practiceQuestions);
            unset($examQuestions);
        }

        // now that we are done, zip all files and return url
        $dirPath = sys_get_temp_dir();
        $filename = "{$exam->getCode()}.zip";
        $zipFilePath = "{$dirPath}/{$filename}";

        if (is_file($zipFilePath)) {
            unlink($zipFilePath);
        }

        $command = "zip $zipFilePath " . implode(" ", $files);
        exec($command, $output, $returnVar);

        // send to S3
        try {
            $response = $this->s3Client->putObject([
                'Bucket' => $this->config->aws->s3->exportContent,
                'Key' => 'exam-exports/'.$filename,
                'SourceFile' => $zipFilePath
            ]);
        } catch (S3Exception $e) {
            $this->logger->error($e);
            return [];
        }

        $command = $this->s3Client->getCommand('GetObject', array(
            'Bucket' => $this->config->aws->s3->exportContent,
            'Key' => 'exam-exports/'.$filename,
        ));

        $signedUrl = $this->s3Client->createPresignedRequest($command, "+5 minutes");

        return [
            'uri' => (string) $signedUrl->getUri()
        ];
    }
}