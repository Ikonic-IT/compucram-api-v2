<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 12/5/2015
 * Time: 8:59 PM
 */

namespace Hondros\Api\Console\Job\Question;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hondros\Api\MessageQueue;

/**
 * Class AddAllToElasticsearch
 * @package Hondros\Api\Console\Job\Question
 * @todo combine this with AddToElasticsearch
 */
class AddAllToElasticsearch extends Command
{
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
     * @return \ElasticSearch\Client
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

    protected function configure()
    {
        $this->setName("question:addAllToElasticsearch")
            ->setDescription("Re-indexes all questions in Elasticsearch. Increments version if already there.");
    }

    /**
     * Populate all questions in elasticsearch
     *
     * Can just rerun as ES will just re-version each existing index with same ids
     *
     * @todo need to rethink where we store index/type strings
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startQuestionId = 0;
        $sampleSize = 5000;

        // clear out es before we start
        $this->getElasticsearch()->indices()->delete(['index' => 'questions']);

        do {
            $output->writeln("Adding {$sampleSize} at id {$startQuestionId}");

            $dql = "
                SELECT q, a
                FROM \Hondros\Api\Model\Entity\Question q
                INNER JOIN q.answers a
                WHERE q.id > :id
                GROUP BY q.id
                ORDER by q.id ASC
            ";

            $query = $this->getEntityManager()
                ->createQuery($dql)
                ->setParameter('id', $startQuestionId)
                ->setMaxResults($sampleSize);

            $questions = $query->getArrayResult();

            if (empty($questions)) {
                $output->writeln("no more questions to add");
                return;
            }
            $params = ['body' => []];

            // now add to queue
            foreach ($questions as $question) {
                $params['body'][] = [
                    'index' => [
                        '_index' => 'questions',
                        '_type' => 'question',
                        '_id' => $question['id'],
                    ]
                ];

                $newParams = [
                    'questionText' => $question['questionText'],
                    'feedback' => $question['feedback']
                ];

                $answerIndex = 1;
                foreach ($question['answers'] as $answer) {
                    $newParams['answer' . $answerIndex++] = $answer['answerText'];
                }

                $params['body'][] = $newParams;
            }

            // bulk update
            $this->getElasticsearch()->bulk($params);

            // track last question id
            $startQuestionId = $question['id'];

            unset($query);
            unset($questions);
            unset($params);

            // clear up uof
            $this->getEntityManager()->getUnitOfWork()->clear();

        } while (true);

        $output->writeln("finished adding questions");
    }
}