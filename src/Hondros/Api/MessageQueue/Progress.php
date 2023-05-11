<?php
namespace Hondros\Api\MessageQueue;

use Hondros\ThirdParty\Zend\Stdlib\Hydrator\Strategy\Entity\ProgressQuestion;
use Laminas\Config\Config;
use Doctrine\ORM\EntityManager;
use Hondros\Api\Model\Repository;
use Hondros\Api\Model\Entity;
use Hondros\Api\Service;
use PhpAmqpLib\Message\AMQPMessage;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use DateTime;
use Predis\Client as Redis;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Class Progress
 * @package Hondros\Api\Util\Job
 *
 *
 */
class Progress extends JobAbstract
{
    /**
     * track all module attempts which need to be re-scored
     */
    const QUEUE_RECALCULATE_MODULE_ATTEMPT = 'progress_recalculate_module_attempt';

    /**
     * track all progress ids which we need to recalculate scores
     */
    const QUEUE_RECALCULATE_PROGRESS = 'progress_recalculate_progress';

    /**
     * Due to new questions, we want to add new progress questions to existing users
     */
    const QUEUE_ADD_PROGRESS_QUESTIONS = 'progress_questions_to_add';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;
    
    /**
     * @var \Hondros\Api\Service\Progress
     */
    protected $progressService;

    /**
     * @var \Hondros\Api\Service\ModuleAttempt
     */
    protected $moduleAttemptService;

    /**
     * @var \Hondros\Api\Model\Repository\Progress
     */
    protected $progressRepository;

    /**
     * @var \Hondros\Api\Model\Repository\ProgressQuestion
     */
    protected $progressQuestionRepository;
    
    public function __construct(Config $config, Redis $cacheAdapter, AMQPStreamConnection $messageQueue, EntityManager $entityManager, Service\Progress $progressService, Service\ModuleAttempt $moduleAttemptService,
        Repository\Progress $progressRepository, Repository\ProgressQuestion $progressQuestionRepository)
    {
        parent::__construct($config, $cacheAdapter, $messageQueue);

        $this->entityManager = $entityManager;
        $this->progressService = $progressService;
        $this->moduleAttemptService = $moduleAttemptService;
        $this->progressRepository = $progressRepository;
        $this->progressQuestionRepository = $progressQuestionRepository;
    }

    public function addProgressIdsToRecalculateQueue($progressIds, $closeConnection = true)
    {
        // anything found?
        if (empty($progressIds)) {
            return [
                    'added' => 0
            ];
        }

        // connect to queue
        $channel = $this->getConn()->channel();
        $channel->queue_declare(self::QUEUE_RECALCULATE_PROGRESS, false, false, false, false);

        // now add to queue
        foreach ($progressIds as $progressId) {
            $data = [
                    'progressId' => $progressId
            ];

            $msg = new AMQPMessage(json_encode($data), [
                    'delivery_mode' => 2
            ]);

            $channel->basic_publish($msg, '', self::QUEUE_RECALCULATE_PROGRESS);
        }

        // close the channel
        $channel->close();

        // disconnect from messageQueue
        if ($closeConnection) {
            $this->getConn()->close();
        }

        return [
                'added' => count($progressIds)
        ];
    }

    /**
     * All all progresses passed, queue them up to get the question added
     * @param int[] $progressIds
     * @param int $questionId
     * @param bool $closeConnection
     * @return array
     */
    public function addProgressIdsToAddProgressQuestionsQueue($progressIds, $questionId, $closeConnection = true)
    {
        // anything found?
        if (empty($progressIds)) {
            return [
                    'added' => 0
            ];
        }

        // connect to queue
        $channel = $this->getConn()->channel();
        $channel->queue_declare(self::QUEUE_ADD_PROGRESS_QUESTIONS, false, false, false, false);

        // now add to queue
        foreach ($progressIds as $progressId) {
            $data = [
                    'progressId' => $progressId,
                    'questionId' => $questionId
            ];

            $msg = new AMQPMessage(json_encode($data), [
                    'delivery_mode' => 2
            ]);

            $channel->basic_publish($msg, '', self::QUEUE_ADD_PROGRESS_QUESTIONS);
        }

        // close the channel
        $channel->close();

        // disconnect from messageQueue
        if ($closeConnection) {
            $this->getConn()->close();
        }

        return [
                'added' => count($progressIds)
        ];
    }

    /**
     * For an enrollment, find all progress id's to add to the update progress queue
     *
     * We've had times where we need to update all students progresses due to a bug
     *
     * @param int $enrollmentId
     * @return array
     */
    public function addAllProgressForEnrollmentToUpdateProgressQueue($enrollmentId)
    {
        // get all
        $progresses = $this->progressRepository->findFor("enrollment", $enrollmentId);

        // anything found?
        if (empty($progresses)) {
            return [
                'added' => 0
            ];
        }

        // connect to queue
        $channel = $this->getConn()->channel();
        $channel->queue_declare(self::QUEUE_RECALCULATE_PROGRESS, false, false, false, false);

        // now add to queue
        foreach ($progresses as $progress) {
            $data = [
                'progressId' => $progress->getId()
            ];

            $msg = new AMQPMessage(json_encode($data), [
                'delivery_mode' => 2
            ]);

            $channel->basic_publish($msg, '', self::QUEUE_RECALCULATE_PROGRESS);
        }

        // close the channel
        $channel->close();

        // disconnect from messageQueue
        $this->getConn()->close();

        return [
            'added' => count($progresses)
        ];
    }
}