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
 * Class Question
 * @package Hondros\Api\Util\Job
 *
 *
 */
class Question extends JobAbstract
{
    /**
     * queue to track any newly added question
     */
    const QUEUE_ADDED_NEW_MODULE_QUESTION = 'question_added_new_module_question';

    /**
     * queue to track what questions to add to elasticsearch
     */
    const QUEUE_ADD_TO_ELASTICSEARCH = 'question_add_to_elasticsearch';

    /**
     * queue to track any question which status has changed active/inactive
     */
    const QUEUE_STATUS_CHANGE = 'question_status_change';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    
    public function __construct(Config $config, Redis $cacheAdapter, AMQPStreamConnection $messageQueue, EntityManager $entityManager)
    {
        parent::__construct($config, $cacheAdapter, $messageQueue);

        $this->entityManager = $entityManager;

    }

    /**
     * When a new module question is added, we add to the queue in case we need to update progresses to add the new q
     *
     * @param int $moduleQuestionId
     * @param string $type
     * @param int $questionId
     * @return bool
     */
    public function addModuleQuestionToAddedNewQueue($moduleQuestionId, $type, $questionId)
    {
        $channel = $this->getConn()->channel();
        $channel->queue_declare(self::QUEUE_ADDED_NEW_MODULE_QUESTION, false, false, false, false);

        $data = [
            'moduleId' => $moduleQuestionId,
            'type' => $type,
            'questionId' => $questionId
        ];

        $msg = new AMQPMessage(json_encode($data), [
            'delivery_mode' => 2
        ]);
        $channel->basic_publish($msg, '', self::QUEUE_ADDED_NEW_MODULE_QUESTION);

        // close the channel
        $channel->close();

        return true;
    }

    /**
     * As questions change or are added, we need to update the contents in es
     *
     * @todo need to remove deleted questions from es
     * @param int $questionId
     * @return bool
     */
    public function addQuestionToElasticsearchQueue($questionId)
    {
        $channel = $this->getConn()->channel();
        $channel->queue_declare(self::QUEUE_ADD_TO_ELASTICSEARCH, false, false, false, false);

        $data = [
                'questionId' => $questionId
        ];

        $msg = new AMQPMessage(json_encode($data), [
                'delivery_mode' => 2
        ]);
        $channel->basic_publish($msg, '', self::QUEUE_ADD_TO_ELASTICSEARCH);

        // close the channel
        $channel->close();

        return true;
    }

    /**
     * When a question becomes active/inactive, we might have things to do
     * @param int $questionId
     * @return bool
     */
    public function addQuestionToStatusUpdateQueue($questionId)
    {
        $channel = $this->getConn()->channel();
        $channel->queue_declare(self::QUEUE_STATUS_CHANGE, false, false, false, false);

        $data = [
            'questionId' => $questionId
        ];

        $msg = new AMQPMessage(json_encode($data), [
            'delivery_mode' => 2
        ]);
        $channel->basic_publish($msg, '', self::QUEUE_STATUS_CHANGE);

        // close the channel
        $channel->close();

        return true;
    }
}