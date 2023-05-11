<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 10/8/2016
 * Time: 2:43 PM
 */

namespace Hondros\Api\Console\Job\Question;

use Hondros\Api\Util\Helper\StringUtil;
use Knp\Command\Command;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hondros\Api\MessageQueue;
use Hondros\Api\Model\Repository;
use PhpAmqpLib\Channel\AMQPChannel;

class AddToElasticsearch extends Command
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
     * @return \Elasticsearch\Client
     */
    public function getElasticsearch()
    {
        return $this->getServiceManager()->get('elasticsearch');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceManager()->get('entityManager');
    }

    /**
     * @return \Predis\Client
     */
    public function getRedis()
    {
        return $this->getServiceManager()->get('redis');
    }

    protected function configure()
    {
        $this->setName("question:addToElasticsearch")
            ->setDescription("Re-indexes individual question into Elasticsearch");
    }

    /**
     * Add question into elastic search
     *
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = time();
        $ttl = $this->getServiceManager()->get('config')->crontab->question->addToElasticsearch->ttl;
        $maxConsumers = $this->getServiceManager()->get('config')->crontab->question->addToElasticsearch->consumers;
        $processId = getmypid();

        $channel = $this->getMessageQueue()->channel();
        list(,,$consumerCount) = $channel->queue_declare(MessageQueue\Question::QUEUE_ADD_TO_ELASTICSEARCH, false,
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

            $questions = $this->getServiceManager()->get('questionRepository')->findByIdsWithAnswers([$item->questionId]);

            /**
             * might run before the question is in the DB, so try again
             * @todo add some sort of counter to only try x times then remove the message
             */
            if (empty($questions)) {
                sleep(2);
                $output->writeln("no questions found for id {$item->questionId} adding back to queue");
                $channel->basic_nack($msg->delivery_info['delivery_tag'], false, true);
                return;
            }

            $question = $questions[0];
            $params = [
                'index' => 'questions',
                'type' => 'question',
                'id' => $question->getId()
            ];

            $newParams = [
                'questionText' => $question->getQuestionText(),
                'feedback' => $question->getFeedback()
            ];

            $answerIndex = 1;
            foreach ($question->getAnswers() as $answer) {
                $newParams['answer' . $answerIndex++] = $answer->getAnswerText();
            }

            $params['body'] = $newParams;

            // update ES
            $this->getElasticsearch()->index($params);

            // clear up uof
            $this->getEntityManager()->getUnitOfWork()->clear();

            $channel->basic_ack($msg->delivery_info['delivery_tag']);

            //$output->writeln(__CLASS__ . " added question {$question->getId()}.");

            // clean up
            unset($questions);
            unset($question);
            unset($newParams);
            unset($params);
            unset($item);
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume(MessageQueue\Question::QUEUE_ADD_TO_ELASTICSEARCH, '', false,
            false, false, false, $callback);

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