<?php

namespace Hondros\Api\Console\Job\Report;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hondros\Api\Model\Entity;
use Exception;
use DateTime;

/**
 * Class TotalStats
 * @package Hondros\Api\Console\Job\Report
 *
 * Gathering the total number of x stats
 *  # of users
 *  # of enrollments
 *  # of exams
 *  # of modules
 *  # of questions
 *  # of module attempts
 *  # of assessment attempts
 */
class TotalStats extends Command
{
    /**
     * key to store hash
     */
    const CACHE_ID = 'report:totalStats';

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
        $this->setName("report:totalStats")
            ->setDescription("Store total number of assets.");
    }

    /**
     * Track stats for all high level assets
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entities = [
            'users' => Entity\User::class,
            'enrollments' => Entity\Enrollment::class,
            'exams' => Entity\Exam::class,
            'modules' => Entity\Module::class,
            'questions' => Entity\Question::class,
            'moduleAttempts' => Entity\ModuleAttempt::class,
            'moduleAttemptQuestions' => Entity\ModuleAttemptQuestion::class,
            'assessmentAttempts' => Entity\AssessmentAttempt::class,
            'assessmentAttemptQuestions' => Entity\AssessmentAttemptQuestion::class
        ];

        $stats = [];
        foreach ($entities as $key => $value) {
            try {
                $result = $this->getTotalFrom($value);
            } catch (Exception $e) {
                $output->writeln("Unable to get total for {$key} due to " . $e->getMessage());
                $result = 0;
            }

            $stats[$key] = $result;
        }

        // track when we last updated the stats
        $stats['lastModified'] = (new DateTime())->getTimestamp();

        $this->getCacheAdapter()->hmset(self::CACHE_ID, $stats);
    }

    /**
     * Count total for asset
     *
     * @param string $entityClass
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @todo grab from schema DB instead of doing counts
     */
    protected function getTotalFrom($entityClass)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT COUNT(e.id) as total from {$entityClass} e")
            ->getSingleScalarResult();
    }
}
