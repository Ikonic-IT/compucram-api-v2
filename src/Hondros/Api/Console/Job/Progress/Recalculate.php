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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Hondros\Api\MessageQueue;

class Recalculate extends Command
{
    use StringUtil { formatBytes as protected; }

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
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceManager()->get('entityManager');
    }

    /**
     * @return \Hondros\Api\Service\Progress
     */
    public function getProgressService()
    {
        return $this->getServiceManager()->get('ProgressService');
    }

    /**
     * @return \Predis\Client
     */
    public function getCacheAdapter()
    {
        return $this->getServiceManager()->get('redis');
    }

    protected function configure()
    {
        $this->setName("progress:recalculate")
            ->setDescription("Updates information for any progress id in the queue");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = time();
        $ttl = $this->getServiceManager()->get('config')->crontab->progress->recalculate->ttl;
        $maxConsumers = $this->getServiceManager()->get('config')->crontab->progress->recalculate->consumers;
        $processId = getmypid();

        $channel = $this->getMessageQueue()->channel();
        list(,,$consumerCount) = $channel->queue_declare(MessageQueue\Progress::QUEUE_RECALCULATE_PROGRESS, false,
            false, false, false);

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

            // check if this progress id was recently updated and skip
            $cacheId = MessageQueue\Progress::QUEUE_RECALCULATE_PROGRESS . $item->progressId;

            // disabling for now - causes more issues than it fixes
            if (false) {//$this->getCacheAdapter()->exists($cacheId)) {
                $output->writeln("Progress {$item->progressId} already updated recently, skipping.");
            } else {
                //$output->writeln("Trying to update progress for {$item->progressId}");
                $results = $this->getProgressService()->recalculateBasedOnProgressQuestion($item->progressId);
                //$output->writeln("Updated progress {$results['id']}");

                // track in cache
                $this->getCacheAdapter()->setex($cacheId, 180, 1);
            }

            $channel->basic_ack($msg->delivery_info['delivery_tag']);

            // clean up
            unset($results);
            unset($item);

            // clear up uof
            $this->getEntityManager()->getUnitOfWork()->clear();
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume(MessageQueue\Progress::QUEUE_RECALCULATE_PROGRESS, '', false,
            false, false, false, $callback);

        while(count($channel->callbacks)) {
            $memUsage = $this->formatBytes(memory_get_usage(true)) . '/' . $this->formatBytes(memory_get_peak_usage(true));
            //$output->writeln(date('Y-m-d H:i:s') . " starting({$processId}) wait loop. Mem({$memUsage})");

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
