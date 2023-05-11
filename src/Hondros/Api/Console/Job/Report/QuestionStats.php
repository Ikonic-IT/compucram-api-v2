<?php
/**
 * Created by PhpStorm.
 * User: Joey
 * Date: 07/31/2019
 * Time: 8:59 PM
 */

namespace Hondros\Api\Console\Job\Report;

use Hondros\Api\Util\Helper\StringUtil;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class QuestionStats
 * @package Hondros\Api\Console\Job\Report
 */
class QuestionStats extends Command
{
    use StringUtil { formatBytes as protected; }

    /**
     * should end with the question id
     */
    const CACHE_ID = 'report:questionStats:questions';

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
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceManager()->get('entityManager');
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
        $this->setName("report:questionStats")
            ->setDescription("Updates stats for all questions progress.");
    }

    /**
     * Updates stats for all questions progress.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = 100;
        $startQuestionId = 1;
        $startTime = microtime(true);

        // get max question id
        $dql = "
            SELECT MAX(maq.questionId)
            FROM Hondros\Api\Model\Entity\ModuleAttemptQuestion maq
        ";
        $query = $this->getEntityManager()->createQuery($dql);
        $maxQuestionId = $query->getSingleScalarResult();

        do {
//            $output->writeln("Gathering stats >= {$startQuestionId} "
//                . sprintf('%0.2f', microtime(true) - $startTime)
//                . ' to max ' . $maxQuestionId);

            $dql = "
                SELECT maq.questionId, SUM(maq.answered) as answered, SUM(maq.correct) as correct
                FROM Hondros\Api\Model\Entity\ModuleAttemptQuestion maq
                WHERE maq.questionId >= :start AND maq.questionId <= :end
                GROUP BY maq.questionId
                ORDER BY maq.questionId ASC
            ";

            $query = $this->getEntityManager()->createQuery($dql);
            $query->setParameter('start', $startQuestionId);
            $query->setParameter('end', $startQuestionId + $limit);

            $questions = $query->getArrayResult();

            if (empty($questions)) {
                $startQuestionId += $limit + 1;

                continue;
            }

            // now add to redis
            $data = [];
            foreach ($questions as $question) {
                $data[$question['questionId']] = $question['answered'].':'.$question['correct'];
            }

            // bulk insert
            $this->getCacheAdapter()->hmset(self::CACHE_ID, $data);

            // track last question id
            $startQuestionId = $question['questionId'] + 1;

            unset($query);
            unset($questions);
            unset($data);

            // clear up uof
            $this->getEntityManager()->getUnitOfWork()->clear();

        } while ($startQuestionId < $maxQuestionId);

        $output->writeln("finished gathering questions");
    }
}