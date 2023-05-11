<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 12/5/2015
 * Time: 8:59 PM
 */

namespace Hondros\Api\Console\Job\Question;

use Hondros\Api\Util\Helper\StringUtil;
use Knp\Command\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hondros\Api\MessageQueue;

/**
 * Class StatusChange
 * @package Hondros\Api\Console\Job\Question
 *
 * When a question is disabled, we need to update the progress for any student with that question that has
 * activity in that progress in the last x months.
 */
class StatusChange extends Command
{
    use StringUtil { formatBytes as protected; }

    /**
     * How many months back of data do we want
     */
    const MONTHS_BACK = 6;

    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * @return \Laminas\ServiceManager\ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param mixed $serviceManager
     * @return StatusChange
     */
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    /**
     * @return \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    public function getMessageQueue()
    {
        return $this->getServiceManager()->get('messageQueue');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceManager()->get('entityManager');
    }

    /**
     * @return \Hondros\Api\Model\Repository\Question
     */
    public function getQuestionRepository()
    {
        return $this->getServiceManager()->get('questionRepository');
    }

    /**
     * @return \Hondros\Api\MessageQueue\Progress
     */
    public function getProgressMessageQueue()
    {
        return $this->getServiceManager()->get('progressMessageQueue');
    }
    protected function configure()
    {
        $this->setName("question:statusChange")
            ->setDescription("Adds all progress ids to queue who have a question affected by the status change.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = time();
        $ttl = $this->getServiceManager()->get('config')->crontab->question->statusChange->ttl;
        $maxConsumers = $this->getServiceManager()->get('config')->crontab->question->statusChange->consumers;
        $processId = getmypid();

        $channel = $this->getMessageQueue()->channel();
        list(,,$consumerCount) = $channel->queue_declare(MessageQueue\Question::QUEUE_STATUS_CHANGE, false, false,
            false, false);

        if ($consumerCount >= $maxConsumers) {
            exit;
        }

        $output->writeln(date('Y-m-d H:i:s') . " - starting({$processId}) with TTL({$ttl}) and max consumers ({$maxConsumers})");

        $callback = function($msg) use ($output) {
            /** @var AMQPChannel $channel */
            $channel = $msg->delivery_info['channel'];

            // get data
            $item = json_decode($msg->body);

            // make sure we have a db connection
            if ($this->getEntityManager()->getConnection()->ping() === false) {
                $this->getEntityManager()->getConnection()->close();
                $this->getEntityManager()->getConnection()->connect();
            }

            $now = new \DateTime();
            $monthsAgo = $now->sub(new \DateInterval('P' . self::MONTHS_BACK . 'M'));

            $dql = "
                SELECT p.id
                FROM Hondros\Api\Model\Entity\Progress p
                JOIN p.questions pq
                WHERE
                  pq.questionId = :questionId
                  AND p.modified >= {$monthsAgo->getTimestamp()}
            ";

            // find out how many matches
            $query = $this->getEntityManager()->createQuery($dql);
            $query->setParameter('questionId', $item->questionId);
            $progressIds = $query->getArrayResult();

            if (empty($progressIds)) {
                $output->writeln("No progresses found for question {$item->questionId}");
            } else {
//                $output->writeln("Adding " . count($progressIds)
//                    . " progresses to queue for question {$item->questionId} status update");

                $this->getProgressMessageQueue()->addProgressIdsToRecalculateQueue(
                    array_column($progressIds, 'id'), false);
            }

            $channel->basic_ack($msg->delivery_info['delivery_tag']);

            // clean up
            unset($progressIds);
            unset($item);

            // clear up uof
            $this->getEntityManager()->getUnitOfWork()->clear();
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume(MessageQueue\Question::QUEUE_STATUS_CHANGE, '', false, false,
            false, false, $callback);

        while(count($channel->callbacks)) {
            $memUsage = $this->formatBytes(memory_get_usage(true)) . '/' . $this->formatBytes(memory_get_peak_usage(true));
            $output->writeln(date('Y-m-d H:i:s') . " starting({$processId}) wait loop. Mem({$memUsage})");

            if (($startTime + $ttl) < time()) {
                $output->writeln(date('Y-m-d H:i:s') . " ({$processId}) time is up for process, stopping.");
                throw new AMQPTimeoutException("Timeout");
            }

            if (memory_get_usage(true) > 524288000) {
                $output->writeln(date('Y-m-d H:i:s') . " ({$processId}) exceeding memory({$memUsage}), stopping.");
                throw new AMQPTimeoutException("Memory limit");
            }

            $channel->wait(null, false, 120);
        }
    }
}