<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 12/5/2015
 * Time: 8:59 PM
 */

namespace Hondros\Api\Console\Job\Progress;

use Hondros\Api\Util\Helper\StringUtil;
use Knp\Command\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Laminas\ServiceManager\ServiceManager;

class AddQuestion extends Command
{
    use StringUtil { formatBytes as protected; }

    const QUEUE_ADD_PROGRESS_QUESTIONS = 'progress_questions_to_add';

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @return mixed
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param mixed $serviceManager
     * @return Recalculate
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
     * @return \Hondros\Api\MessageQueue\Progress
     */
    public function getProgressMessageQueue()
    {
        return $this->getServiceManager()->get('progressMessageQueue');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceManager()->get('entityManager');
    }

    /**
     * @return \Hondros\Api\Model\Repository\Progress
     */
    public function getProgressRepository()
    {
        return $this->getServiceManager()->get('progressRepository');
    }

    protected function configure()
    {
        $this->setName("progress:addQuestion")
            ->setDescription("Adds progress question for newly added study or practice question");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = time();
        $ttl = $this->getServiceManager()->get('config')->crontab->progress->addQuestion->ttl;
        $maxConsumers = $this->getServiceManager()->get('config')->crontab->progress->addQuestion->consumers;
        $processId = getmypid();

        $channel = $this->getMessageQueue()->channel();
        list(,,$consumerCount) = $channel->queue_declare(self::QUEUE_ADD_PROGRESS_QUESTIONS, false, false, false, false);

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

            $progress = $this->getProgressRepository()->find($item->progressId);

            // @todo need better error handling. If unique constraint fails, all others break with closed manager
            // maybe top this job and let another pick it up, or try to open connection again
            if (!empty($progress)) {
                $progressQuestion = new \Hondros\Api\Model\Entity\ProgressQuestion();
                $progressQuestion->setProgress($this->getEntityManager()->getReference('Hondros\Api\Model\Entity\Progress', $item->progressId));
                $progressQuestion->setQuestion($this->getEntityManager()->getReference('Hondros\Api\Model\Entity\Question', $item->questionId));

                try {
                    $this->getEntityManager()->persist($progressQuestion);
                    $this->getEntityManager()->flush();
                    //$output->writeln("Added question to {$item->progressId} for {$item->questionId}");
                } catch (\Exception $e) {
                    // track it but still remove from queue
                    $output->writeln("Unable to add question {$item->progressId} for {$item->questionId} due to {$e->getMessage()} {$e->getCode()}");
                }

                // add to recalc queue
                $this->getProgressMessageQueue()->addProgressIdsToRecalculateQueue([$item->progressId], false);

            } else {
                $output->writeln("Unable to find progress {$item->progressId}.");
            }

            $channel->basic_ack($msg->delivery_info['delivery_tag']);

            // clean up
            unset($progress);
            unset($progressQuestion);
            unset($item);

            // clear up uof
            $this->getEntityManager()->getUnitOfWork()->clear();
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume(self::QUEUE_ADD_PROGRESS_QUESTIONS, '', false, false, false, false, $callback);

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
